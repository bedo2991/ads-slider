import dayjs from 'dayjs/esm/index.js'
import { AdsEvent } from '../types.js'


export enum EventStatuses {
  preparation = "PREP",
  final_round = "FIN",
  closing = "CLOSING",
  closed = "CLOSED",
  happy_hour = "HH",
}

export enum ScheduleReason {
  started = "STARTED",
  running = "RUNNING",
  ended = "ENDED",
  refresh = "REFRESH",
}

export default class EventStatus {
  event: AdsEvent;

  constructor(event: AdsEvent) {
    this.event = event;
    this.event.startDate = dayjs(this.event.startDate['$d'].toString());
    this.event.endDate = dayjs(this.event.endDate['$d'].toString());
  }

  startsAt(): dayjs.Dayjs {
    return this.event.startDate.clone();
  }

  endsAt(): dayjs.Dayjs {
    return this.event.endDate.clone();
  }

  finalRoundStart(): dayjs.Dayjs | null {
    if (!this.event.final_round_confirmed || this.event.cancelled || this.event.not_closing) {
      return null;
    }
    return this.event.endDate.subtract(30, 'minutes');
  }

  finalRoundEnd(): dayjs.Dayjs | null {
    if (!this.event.final_round_confirmed || this.event.cancelled || this.event.not_closing) {
      return null;
    }
    return this.event.endDate.subtract(15, 'minutes');
  }

  hasPreparationTime(): boolean {
    return this.event.preparation_time !== 0 && !this.event.cancelled;
  }

  preparationTimeStart(): dayjs.Dayjs | null {
    return this.hasPreparationTime() ? this.event.startDate.subtract(this.event.preparation_time ?? 30, 'minutes') : null;
  }

  preparationTimeEnd(): dayjs.Dayjs | null {
    return this.hasPreparationTime() ? this.event.startDate.clone() : null;
  }

  hasLastFifteenMinutes(): boolean {
    return !this.event.cancelled && !this.event.not_closing;
  }

  lastFifteenMinutesStart(): dayjs.Dayjs | null {
    return this.hasLastFifteenMinutes() ? this.event.endDate.subtract(15, 'minutes') : null;
  }

  lastFifteenMinutesEnd(): dayjs.Dayjs | null {
    return this.hasLastFifteenMinutes() ? this.event.endDate.clone() : null;
  }


  hasThirtyMinutesAfter(): boolean {
    return !this.event.cancelled && !this.event.not_closing;
  }

  thirtyMinutesAfterStart(): dayjs.Dayjs | null {
    return this.hasThirtyMinutesAfter() ? this.event.endDate.clone() : null;
  }

  thirtyMinutesAfterEnd(): dayjs.Dayjs | null {
    return this.hasThirtyMinutesAfter() ? this.event.endDate.add(30, 'minutes') : null;
  }

  hasHappyHour(): boolean {
    return (!!this.event.happy_hour && !this.event.cancelled);
  }

  happyHourStart(): dayjs.Dayjs | null {
    return this.hasHappyHour() ? dayjs(this.event.happy_hour?.start) : null;
  }

  happyHourEnd(): dayjs.Dayjs | null {
    return this.hasHappyHour() ? dayjs(this.event.happy_hour?.end) : null;
  }
}
