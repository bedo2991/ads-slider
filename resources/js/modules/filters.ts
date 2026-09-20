import dayjs from 'dayjs/esm/index.js'
import isBetween from 'dayjs/esm/plugin/isBetween/index.js'
import type {Config, PictureSlide, VideoSlide, AdsEvent} from '../types.js'

dayjs.extend(isBetween);

export default class Filter2 {
  timeOffset: number;
  config: Config;

  constructor(config : Config, timeOffset = 0) {
    this.timeOffset = timeOffset;
    this.config = config;
  }

  updateTimeOffset(timeOffset: number) {
    this.timeOffset = timeOffset;
  }

  getNow(): dayjs.Dayjs {
    return dayjs().add(this.timeOffset, 'milliseconds');
  }

  showElementOnThisMonitor(element : {monitors:{id:number}[]}): boolean {
    if (element.monitors?.length === 0) {
      // if no monitors are specified, show on all monitors
      return true;
    }
    return element.monitors?.some((mo) => mo.id === this.config.id);
  }

  pictures(pictureSlide: PictureSlide): boolean {
    return (
      this.getNow().isBetween(pictureSlide.startDate, pictureSlide.endDate)
    );
  }

  videos(videoSlide: VideoSlide): boolean {
    return (
      this.getNow().isBetween(videoSlide.startDate, videoSlide.endDate)
    );
  }

  isAds(event : AdsEvent) : boolean {
    if (event.disabled === true) {
      return false;
    }
    if (!this.config.show_cancelled_events && event.cancelled === true) {
      return false;
    }
    const now = this.getNow();
    if (this.config.show_event_while_is_happening) {
      if (event.repeat) {
        if (now.isSame(event.startDate, 'day')) {
          return now.isBefore(event.endDate);
        }
        return false;
      }
      return now.isBefore(event.endDate);
    }

    if (event.repeat) {
      if (now.isSame(event.startDate, 'day')) {
        return now.isBefore(event.startDate);
      }
      return false;
    }
    return now.isBefore(event.startDate);
  }

  isFinalRound(event : AdsEvent) : boolean {
    const diff = this.getNow().diff(event.endDate);
    return (
      event.final_round_confirmed === true
      && event.cancelled === false
      && event.not_closing === false
      && diff >= -1800000
      && diff < -900000
    );
  }

  isLast15Minutes(event : AdsEvent) : boolean {
    // return true to keep, return false to filter out
    const diff = this.getNow().diff(event.endDate);
    return (
      event.cancelled === false
      && event.not_closing === false
      && diff >= -900000
      && diff < 0
    );
  }

  is30MinutesAfterEvent(event : AdsEvent)  : boolean {
    const diff = this.getNow().diff(event.endDate);
    return (
      event.cancelled === false
      && event.not_closing === false
      && diff >= 0
      && diff < 1800000
    );
  }

  isPreparationTime(event : AdsEvent)  : boolean {
    const diff = this.getNow().diff(event.startDate);
    if (event.preparation_time && event.preparation_time > 0) {
      return (
        event.cancelled === false
        && diff >= -event.preparation_time * 60000
        && diff < 0
      );
    }
    return (
      event.cancelled === false
      && diff >= -1800000
      && diff < 0
    );
  }

  isHappyHour(event : AdsEvent)  : boolean{
    // If the event was cancelled, the happy hour should also not be shown
    if (event.cancelled) return false;
    if (event.happy_hour && typeof event.happy_hour === 'object' && event.cancelled === false) {
      const happyHourStart = dayjs(event.happy_hour.start);
      const happyHourEnd = dayjs(event.happy_hour.end);
      return this.getNow().isBetween(happyHourStart, happyHourEnd);
    }
    return false;
  }

  isHappeningNow(event : AdsEvent)  : boolean{
    return !event.cancelled && this.getNow().isBetween(event.startDate, event.endDate);
  }

  static isNotDisabled(value : {disabled?:boolean})  : boolean{
    return !value.disabled;
  }

  static eventHasMenus(event : AdsEvent) : boolean {
    // If the event was cancelled, the menus should also not be shown
    if (event.cancelled) return false;
    if (!event.menus || event.menus.length === 0) return false;
    return true;

    //return event.menus.map((m : MenuSimple) => m.id).length > 0; // .some(this.showElementOnThisMonitor.bind(this));
    // return value.menus.length > 0;
    // getMenusFromEvent(value).length > 0;
    // return (typeof (value.menus_bar) === "object" || typeof (value.menus_tresen) === "object" );
  }
}
