import dayjs from 'dayjs/esm/index.js'
import EventStatus, { EventStatuses, ScheduleReason } from './modules/eventStatus.js';
import isSameOrAfter from 'dayjs/esm/plugin/isSameOrAfter/index.js';
import type { AdsEvent } from './types.js';

const SCHEDULER_TICK = 20000;

dayjs.extend(isSameOrAfter);

export interface SchedulerItem {
  event_id: number;
  subject: EventStatuses;
  deadline: dayjs.Dayjs;
  until?: dayjs.Dayjs;
  scheduled?: boolean;
  priority: number; // higher: better
}

interface SchedulerConfig {
  show_happy_hours: boolean,
  show_preparation_countdowns: boolean,
  show_final_rounds: boolean,
  show_we_are_closing: boolean,
  show_we_are_closed_marketing: boolean
}

export enum SchedulerActions {
  startProcessing,
  stopListening,
  addScheduleItem,
  removeScheduleItem,
  evaluateEvents,
  //setTimer,
  //clearTimer,
}

let s: SchedulerItem[] = [];
let lastCheck: dayjs.Dayjs = dayjs();
const config: SchedulerConfig = {
  show_happy_hours: true,
  show_preparation_countdowns: true,
  show_final_rounds: true,
  show_we_are_closing: true,
  show_we_are_closed_marketing: true
};

//const filter = new Filter(config, 0);

let next: SchedulerItem | null = null;
let current: SchedulerItem | null = null;
//let schedulerInterval: ReturnType<typeof setInterval> | null = null;

onmessage = function receiveMessage(m) {
  console.log('[sched] Scheduler Worker: Message received from main script: ', m);
  switch (m.data.action) {

    case SchedulerActions.startProcessing:
      startListening();
      break;

    case SchedulerActions.stopListening:
      stopListening();
      break;

    case SchedulerActions.addScheduleItem:
      addScheduleItem({ event_id: m.data.id, subject: m.data.subject, deadline: dayjs(m.data.deadline['$d'].toString()), priority: m.data.priority });
      break;

    case SchedulerActions.removeScheduleItem:
      removeScheduleItem(m.data.item);
      break;

    case SchedulerActions.evaluateEvents:
      evaluateEvents(m.data.events);
      break;

    default:
      console.error('[sched] Scheduler Worker: Invalid action received: ', m.data.action);
  }
};

function runClock() {
  const now = new Date();
  const timeToNextTick = (60 - now.getSeconds()) * 1000 - now.getMilliseconds();
  setTimeout(function () {
    evaluateSchedule();
    runClock();
  }, timeToNextTick);
}

// start it


function startListening(): void {
  evaluateSchedule();
  runClock();
  /*if (schedulerInterval === null) {
    console.log("[sched] Scheduler Worker: Starting to listen for schedule items.")
    schedulerInterval = setInterval(evaluateSchedule, SCHEDULER_TICK);
    evaluateSchedule();
  }*/
}

function stopListening(): void {
  throw new Error("Not implemented");
}

function evaluateSchedule(): void {
  console.debug("[sched] Scheduler Worker: Evaluating items.", s);
  console.log("[sched] Next", next);
  const now = dayjs();

  if (!now.isSame(lastCheck, 'day')) {
    // Day change over midnight
    console.log("[sched] Day change over midnight");
    lastCheck = now;
    fireDateChangeEvent();
    return;
  }
  lastCheck = now;

  if (!next) { // if there is nothing afterwards
    if (current) { // but there is still something running
      if (current.until?.isBefore(now)) { //check if it is over
        currentEnded();
        evaluateSchedule();
        return;
      }
    }
    return;
  }


  // evaluate if next should become current
  if (current) {
    console.log("[sched] Current is present", current);
    // check if current is expired
    if (current.until?.isBefore(now)) {
      currentEnded();
      evaluateSchedule();
      return;
    }
    if (current !== next) {
      console.log("[sched] Current is not next");
      if (next.deadline.isAfter(now)) {
        // not started yet, can't be current
        console.log("[sched] Next not started yet, can't be current");
      } else if (next.until?.isBefore(now)) {
        // already ended, can't be current
        console.log("[sched] Next is already expired");
      } else if (next.priority > current.priority) {
        console.log("[sched] Next has higher priority, switching.");
        requestSchedule(ScheduleReason.running, true);
        return;
      }
    }
    return;
  }


  if (next.deadline?.isBefore(now)) {

    // If it just starts
    if (!next.until) {
      requestSchedule(ScheduleReason.started);
      return;
    }

    // If it is still running
    if (next.until.isAfter(now)) {
      if (next.scheduled !== true) {
        requestSchedule(ScheduleReason.running, true);
      } // else, it was already requested
    } else {
      // it is expired
      if (next.scheduled) {
        requestSchedule(ScheduleReason.ended);
        evaluateSchedule();
      } else {
        // it is over and was not scheduled, skip it.
        removeScheduleItem(next);
        evaluateSchedule();
      }
    }
  }
}

function evaluateEvent(e: AdsEvent, now: dayjs.Dayjs): void {
  console.log("[sched] Evaluate Event");
  const eventStatus = new EventStatus(e);

  if (config.show_preparation_countdowns) {
    const prep = eventStatus.preparationTimeStart();
    if (prep) {
      const prepEnd = eventStatus.startsAt();

      if (prep.isAfter(now) || prepEnd.isAfter(now)) {
        addScheduleItem({
          event_id: e.id,
          subject: EventStatuses.preparation,
          deadline: prep,
          until: prepEnd,
          priority: 80
        });
      }
    }
  }

  if (config.show_final_rounds) {
    const fr = eventStatus.finalRoundStart();
    if (fr) {
      const frEnd = fr.add(15, 'minutes');
      if (fr.isAfter(now) || frEnd.isAfter(now)) {
        addScheduleItem({
          event_id: e.id,
          subject: EventStatuses.final_round,
          deadline: fr,
          until: frEnd,
          priority: 70
        });
      }
    }
  }

  if (config.show_we_are_closing) {
    const closing = eventStatus.lastFifteenMinutesStart();
    if (closing) {
      const closingEnds = closing.add(15, 'minutes');
      if (closing.isAfter(now) || closingEnds.isAfter(now)) {
        addScheduleItem({
          event_id: e.id, subject: EventStatuses.closing,
          deadline: closing,
          until: closingEnds,
          priority: 60
        });
      }
    }
  }

  if (config.show_we_are_closed_marketing) {
    const closed = eventStatus.thirtyMinutesAfterStart();
    if (closed) {
      const closedEnds = closed.add(30, 'minutes');
      if (closed.isAfter(now) || closedEnds.isAfter(now)) {
        addScheduleItem({
          event_id: e.id,
          subject: EventStatuses.closed,
          deadline: closed,
          until: closedEnds,
          priority: 50
        });
      }
    }
  }

  if (config.show_happy_hours) {
    const hh = eventStatus.happyHourStart();
    const hhEnd = eventStatus.happyHourEnd();
    if (hh && hhEnd) {
      if (hh.isAfter(now) || hhEnd.isAfter(now)) {
        addScheduleItem({
          event_id: e.id,
          subject: EventStatuses.happy_hour,
          deadline: hh,
          until: hhEnd,
          priority: 90
        });
      }
    }
  }
}

function evaluateEvents(events: AdsEvent[]): void {
  console.log("[sched] Evaluate events")
  s = [];

  if (current) {
    console.log("[sched] Current was present, removing it.")
    currentEnded();
  }
  next = null;

  const now = dayjs();
  events.forEach(e => {
    evaluateEvent(e, now);
  });
  updateNext();
  evaluateSchedule();
}

function addScheduleItem(item: SchedulerItem): void {
  console.log("[sched] Adding to schedule: ", item);
  s.push(item);

  let nextChanged = false;

  if (!next) {
    next = item;
    nextChanged = true;
  }
  else {
    if (item.deadline.isBefore(next.deadline)) {
      next = item;
      nextChanged = true;
    } else if (item.deadline.isSame(next.deadline)) {
      if (item.priority > next.priority) {
        next = item;
        nextChanged = true;
      }
    }
  }

  // if next has changed
  if (nextChanged && current) {
    console.log("[sched] Next has changed and there is a current event running")
    const now = dayjs();
    if (item.deadline.isAfter(now)) {
      // not started yet, can't be current
      return;
    }
    if (item.until?.isBefore(now)) {
      // already ended, can't be current
      return;
    }
    // check if next has higher priority than current
    if (item.priority > current.priority) {
      requestSchedule(ScheduleReason.running, true);
    }


  }
}

function updateNext() {
  console.log("Updating next");

  const now = dayjs();
  s.filter(i => i.until ? i.until.isAfter(now) : i.deadline.isAfter(now));
  // sort the items based on the deadline and priority
  s.sort((a, b) => {
    const diff = a.deadline.diff(b.deadline);
    if (diff === 0) {
      return a.priority - b.priority;
    }
    return diff;
  });

  next = s.length > 0 ? s[0] : null;
  console.log("Next is", next);
}

function removeScheduleItem(item: SchedulerItem): void {
  console.log("[sched] Removing from schedule: ", item);
  s = s.filter(i => i !== item);
  if (next === item) {
    console.log("[sched] The item being removed was NEXT, updating next");
    updateNext();
  }
}

function currentEnded(): void {
  if (!current) {
    throw new Error("[sched] Current is null!");
  }
  console.log("[sched] Current removed");
  //if ([EventStatuses.closed, EventStatuses.closing].includes(current.subject)) {
  // TODO: this is required because the closed and closing events do not have a timer
  // find a more elegant way to realize this
  // it might be that at the moment this is sent twice.
  // if commented out, cancelling a countdown does not work (e.g. preparation if the starting time is changed to later)
  postMessage({ 'schedule': current, 'reason': ScheduleReason.ended });
  //}
  current = null;
}

function requestSchedule(reason: ScheduleReason, keep = false): void {
  if (!next) {
    throw new Error("RequestSchedule: Next is null!");
  }

  console.log("[sched] Requesting schedule for ", next);
  console.log("[sched] Reason: ", reason);
  postMessage({ 'schedule': next, 'reason': reason });

  if (current && current.until?.isAfter(dayjs())) {
    console.log("[sched] Next is replacing the old current");
    const oldCurrent = current;
    current = null;
    addScheduleItem(oldCurrent);
  }

  if (!keep) {
    current = null;
  } else if (next.until) {
    current = next;
    next.scheduled = true;
  } else {
    throw ("Next should have an until value set to become current, as the method as been called with keep=true.");
  }
  removeScheduleItem(next);

}

function fireDateChangeEvent(): void {
  postMessage({ 'dayChange': true });
}

console.warn("[sched] schedulerWorker is running");