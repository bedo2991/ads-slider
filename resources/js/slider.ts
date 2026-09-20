import dayjs from 'dayjs/esm/index.js'
import schedulerWorkerURL from './schedulerWorker.ts?worker&url';
import videosWorkerURL from './videosWorker.ts?worker&url';


// workaround for vite workers
const schedulerWorkerBlob = new Blob([`import ${JSON.stringify(new URL(schedulerWorkerURL, import.meta.url))}`], { type: "text/javascript" });
const videosWorkerBlob = new Blob([`import ${JSON.stringify(new URL(videosWorkerURL, import.meta.url))}`], { type: "text/javascript" })
function workaroundWorker(blob: Blob, options?: { name: string }) {
  const objURL = URL.createObjectURL(blob)
  const worker = new Worker(objURL, { type: "module", name: options?.name })
  worker.addEventListener("error", (/*e*/) => {
    URL.revokeObjectURL(objURL)
  })
  return worker;
}

//instead of just:

//import SchedulerWorker from './schedulerWorker.js?worker';
//import VideosWorker from './videosWorker.js?worker';

// end: workaround for vite workers

import Echo, { ConnectionStatus } from 'laravel-echo';
import Pusher from 'pusher-js'
import Filter from './modules/filters.js';
import $ from 'jquery';
import _ from './localization.js';
import '../sass/slider.scss';
import './bootstrap.js';
import type { Config, PictureSlide, VideoSlide, AdsEvent, InitialServerData, Menu, ElementWithRealStartDate, ServerData, WeatherData, OrderslistData } from './types.js';
import QR from 'qrcode';
import isSameOrAfter from 'dayjs/esm/plugin/isSameOrAfter/index.js';
import localizedFormat from 'dayjs/esm/plugin/localizedFormat/index.js';
//const isSameOrAfter = require('dayjs/plugin/isSameOrAfter');
//const localizedFormat = require('dayjs/plugin/localizedFormat');
import 'dayjs/esm/locale/de';
import 'dayjs/esm/locale/it';
import { getFormattedTime } from './utilities/time.js';
import { SchedulerActions, SchedulerItem } from './schedulerWorker.js';
import { MarketingAfter } from './slides/MarketingAfter.js';
import { InterruptionSlides, Manager, ScheduledSlideType } from './manager.js';
import { EventStatuses, ScheduleReason } from './modules/eventStatus.js';
import { WeatherForecastSlide } from './slides/WeatherForecastSlide.js';
import { PicsSlide } from './slides/PicsSlide.js';
import { VidsSlide } from './slides/VidsSlide.js';
import { EventsSlide } from './slides/EventsSlide.js';
import { PreparationSlide } from './slides/PreparationSlide.js';
import { ClosingSlide } from './slides/ClosingSlide.js';
import { MiniSlide } from './slides/MiniSlides.js';
import { LastCallSlide } from './slides/LastCallSlide.js';
import { HappyHourSlide } from './slides/HappyHourSlide.js';
import { MenuSlide } from './slides/MenuSlide.js';
import { OrdersListSlide } from './slides/OrdersListSlide.js';
import { fillInComponentSafe } from './utilities/misc.js';
//import * as WeatherSlide from './slides/weather';

const data: InitialServerData = window.getData();

enum AlertLevel {
  Info, Warning, Error, Catastrophy
}

declare interface NinaAlert extends Alert {
  start?: string;
  end?: string;
}

declare interface Alert {
  title: string;
  title_en?: string | null;
  url: string;
  message: string;
  message_en?: string | null;
  level: AlertLevel;
  timeoutInSeconds: number;
  source: string;
}


declare global {
  interface Window {
    Echo: Echo<"reverb">;
    Pusher: typeof Pusher;
    getData: () => InitialServerData;
  }
}

window.Pusher = Pusher;

window.Echo = new Echo({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT,
  wssPort: import.meta.env.VITE_REVERB_PORT,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
  enabledTransports: ['ws', 'wss']
});

//require('dayjs/locale/de');

const config: Config = {
  id: data.m.id,
  locale: data.locale,
  name: data.m.name,
  events_to_show: data.m.events_to_show,
  show_preparation_countdowns: !!data.m.show_preparation_countdowns,
  show_final_rounds: !!data.m.show_final_rounds,
  show_we_are_closing: !!data.m.show_we_are_closing,
  show_we_are_closed_marketing: !!data.m.show_we_are_closed_marketing,
  show_cancelled_events: !!data.m.show_cancelled_events,
  show_menus: !!data.m.show_menus,
  show_orderslist: !!data.m.show_orderslist,
  show_happy_hours: !!data.m.show_happy_hours,
  show_pictures: !!data.m.show_pictures,
  show_videos: !!data.m.show_videos,
  show_karaoke: false,// currently disabled !!data.m.show_karaoke,
  show_weather_forecast: !!data.m.show_weather_forecast,
  use_animations: !!data.m.use_animations,
  show_marquee: false, // currently disabled !!data.m.show_marquee,
  show_event_while_is_happening: !!data.m.show_event_while_is_happening,
  api_token: data.m.api_token,
  realm_id: data.m.realm_id,
  channel_hash: data.m.channel_hash ?? '',
  base_root: import.meta.env.VITE_APP_URL ? import.meta.env.VITE_APP_URL + "/" : "/",
  debug_level: import.meta.env.VITE_APP_ENV === 'local' ? 'debug' : 'warning', // warning, info, debug
} as const;

// Delete all monitor values, config. should be used from here on
// data.m = null; does not go well with ts

switch (config.debug_level) {
  case 'warning':
    console.log = () => undefined;
    console.dir = () => undefined;
    console.debug = () => undefined;
    break;
  case 'info':
    console.debug = () => undefined;
    break;
  case 'debug':
    // nothing, all functions are enabled
    break;
  default:
    break;
}
const filter = new Filter(config, 0);

if (config.locale !== 'en') {
  dayjs.locale(config.locale);
} // otherwise defaults to en
dayjs.extend(localizedFormat);
dayjs.extend(isSameOrAfter);

const params = new Proxy(new URLSearchParams(window.location.search), {
  get: (searchParams, prop: string) => searchParams.get(prop),
});

let timeOffset = 0;
let alertTimeout: ReturnType<typeof setTimeout> | null = null;

let currentVersion: string;

/*enum Interval{
  Slides, Small_slides, Menu, Pics, Videos
}*/
const intervalKeys = {
  SLIDES: 'slides',
  SMALL_SLIDES: 'small_slides',
  MENU: 'menu'
} as const;

const intervalsObject: object = {};
Object.values(intervalKeys).forEach((k) => {
  intervalsObject[k] = null;
});

Object.seal(intervalsObject);

let isOffline: boolean;
let isWsOffline = false;
let weatherDataLastUpdate: dayjs.Dayjs;
let weatherOffline = false;
let animEnd: string;

let videosWorker: Worker; // The worker instance that caches the videos
let schedulerWorker: Worker;

//const eventsChangedPublisher = new EventsEventsListener

let eventsSlide: EventsSlide | null;

let weatherForecastSlide: WeatherForecastSlide | null;
let picsSlide: PicsSlide | null;
let vidsSlide: VidsSlide | null;
let menuSlide: MenuSlide | null;
let ordersListSlide: OrdersListSlide | null;

let prepSlide: PreparationSlide | null;
let marketingAfterSlide: MarketingAfter | null;
let closingSlide: ClosingSlide | null;
let lastCallSlide: LastCallSlide | null;
let happyHourSlide: HappyHourSlide | null;

const manager = Manager.Instance;
manager.init(config);

let e: AdsEvent[];

function initDataWithStartAndEndDate(source: ElementWithRealStartDate[] | null): ElementWithRealStartDate[] {
  const res: ElementWithRealStartDate[] = [];
  if (!source) return res;
  // Update the dates for the items with repeat.
  Object.values(source).forEach((element) => {
    res.push(computeStartAndEndDates(element));
  });
  return res;
}

function computeStartAndEndDates(item: ElementWithRealStartDate): ElementWithRealStartDate {
  item.startDate = dayjs(item.real_start_date);
  item.endDate = dayjs(item.real_end_date);
  return item;
}

function animateDataLoadFail(): void {
  $('#clock').animateCss('flash');
}

function animateDataLoadSuccess(): void {
  $('#clock').animateCss('heartBeat');
}

function pullData() {
  console.debug('[Data] pullData()');
  $.getJSON(
    `${config.base_root}Content/${config.api_token}/data.json`,
  )
    .fail((jqxhr, textStatus, error) => {
      // fail
      console.error('[Data] Cannot load data.', error);
      isOffline = true;
      animateDataLoadFail();
      setTimeout(pullData, 60000); // Retry in 60 seconds...
    })
    .done((data/*, status, request: JQuery.jqXHR*/) => {
      isOffline = false;
      //updateTimeOffset(request);
      checkVersion(data.version);
      updateData(data);
      afterDataUpdate();
    });
}

function afterDataUpdate() {
  animateDataLoadSuccess();
}

function sendEventsDataToScheduler(events: AdsEvent[]) {
  schedulerWorker.postMessage({
    action: SchedulerActions.evaluateEvents,
    events: events
  });
}

function setSchedulerTimer(id: number, state: EventStatuses, dateTime: dayjs.Dayjs) {
  schedulerWorker.postMessage({
    action: SchedulerActions.addScheduleItem,
    deadline: dateTime,
    subject: state,
    id: id
  });
}

function getEventById(id: number): AdsEvent | undefined {
  return e.find((event) => event.id === id);
}

function updateData(data: ServerData): void {
  if (params['d'] === 'true') {

    // In debug mode we still need to track the version to avoid auto-reloads
    // when checkVersion() compares against an undefined currentVersion.
    currentVersion = data.version;

    logToDebug("Dynamic Data Update", data);
    return;
  }
  let videos: VideoSlide[] = [];
  let pics: PictureSlide[] = [];
  currentVersion = data.version;

  if (menuSlide) {
    menuSlide.setMenus(data.menus);
  }

  e = initDataWithStartAndEndDate(data.e) as AdsEvent[];

  e.forEach((event) => {
    if (event.menus && typeof event.menus === 'object') {
      event.menus = Object.values(event.menus);
    }
  });

  sendEventsDataToScheduler(e);
  //eventsChangedPublisher.notifySubscribers("updated");

  eventsSlide?.setEvents(e);
  MiniSlide.Instance.setEvents(e);
  // TODO:
  //eventsSlide?.setMarqueeText(marquee);

  if (picsSlide) {
    pics = initDataWithStartAndEndDate(data.p) as PictureSlide[];
    picsSlide.setPictures(pics);
  }

  if (vidsSlide) {
    videos = initDataWithStartAndEndDate(data.v) as VideoSlide[];
    vidsSlide.setVideos(videos);
  }

  if (menuSlide) {
    menuSlide.setEvents(e);
  }

  if (videos.length > 0) {
    videos = videos.filter(filter.videos.bind(filter));
    if (videos.length === 0) {
      console.warn('All videos filtered');
    } else {
      videos.forEach((v: VideoSlide) => {
        let expirationTimeStamp: number;
        if (v.end) {
          expirationTimeStamp = new Date(v.end).getTime();
        } else {
          const exp = new Date();
          // Pick a random number btw 30 and 60
          // * (60 - 30 + 1) + 30)   === (max - min + 1) + min)
          const expireInXDays = Math.floor(Math.random() * 31 + 30);
          exp.setDate(exp.getDate() + expireInXDays);
          expirationTimeStamp = exp.getTime();
        }
        videosWorker.postMessage({ action: 'FETCH', id: v.scheduleable_id, expiration: expirationTimeStamp });
      });
    }
  } else {
    console.warn('Received 0 videos');
  }

  if (data?.weather) {
    prepareWeatherSlide(data.weather);
  }

  if (ordersListSlide) {
    const ordersListData: OrderslistData = data?.ol;
    if (ordersListData) {
      ordersListSlide.updateData(ordersListData);
    } else {
      console.warn('No orders list data received');
    }
  }
}

const clock = $('#clock');

function removeAnimations(): void {
  const animatedElements = document.querySelectorAll('.animate__animated');
  animatedElements.forEach((el) => el.classList.remove('animate__animated'));
}

/**
 * Forces reloading the page when an internet connection is available
 */
function reloadPage(): void {
  if (navigator.onLine) {
    window.location.reload();
  } else { // Retry in 10 seconds
    setTimeout(() => reloadPage, 10000);
  }
}

function checkVersion(newVersion: string): void {
  if (currentVersion !== null) {
    if (newVersion && currentVersion !== newVersion) {
      console.warn(
        'Different version received, refreshing the page',
        newVersion,
      );
      reloadPage();
      return;
    }
    console.debug('[checkVersion] version remained the same.');
  } else {
    console.debug(`[checkVersion] Initialization of the current version to: ${newVersion}`);
    currentVersion = newVersion;
  }
}

function stopLoading(): void {
  loadingMessage('all done!');
  $('#loading-container').animateCss('fadeOut', () => {
    $('#loading-container').remove();
  });
}

function loadingMessage(message: string): void {
  $('#loading-message').text(message);
}

function fatalError(message: string): never {
  $('#fatal-error-message').text(message);
  $('#fatal-error-container').show();
  throw new Error(`Fatal error: ${message}`);
}

/**
 * @deprecated
 * @returns 
 */
function getNow(): dayjs.Dayjs {
  // return dayjs("2019-01-01T12:00:00"); //Use for testing purposes
  return dayjs();//.add(timeOffset, 'milliseconds');
}

// console.debug('Extending $');
$.fn.extend({
  animateCss(this: JQuery, animationName: string, callback?: () => void) {
    console.debug(`[Animation] adding animation "${animationName}"`);
    if (!config.use_animations) {
      console.debug(
        '[Animation] animations are disabled by the monitor configuration',
      );
      // Disable showing animations by calling the callback directly
      if (typeof callback === 'function') {
        callback();
      }
      return this; // Skip setting the animation
    }
    // console.debug('AnimateCSS');
    // console.debug('This', this);
    this.show()
      .addClass(`animate__animated animate__${animationName}`)
      .one(animEnd, function animationEndCallback() {
        console.debug('[Animation] AnimationEnded fired: ' + animationName);
        if (animationName.includes('Out')) {
          if ($(this).hasClass('event-details')) {
            $(this).hide();
            $('#left-part-container').hide();
          }
        }
        $(this).removeClass(`animate__${animationName}`);

        if (typeof callback === 'function') {
          callback();
        }
      });
    // console.debug('[Animation] This with event', this);

    return this;
  },
});

function updateClock() {
  const now = dayjs();
  const oldText = clock.text();
  const newText = (navigator.onLine ? '' : '! ') + (isWsOffline ? '⚠ ' : '') + (weatherOffline ? '☁' : '') + now.format('LT') + (isOffline ? '.' : '');
  if (oldText !== newText) {
    clock.text(newText);
    // Random values between "bottom": 0.5 and 1.5 and "right": between 1 and 3.
    clock
      .css('bottom', `${0.5 + Math.random() * 0.5}vh`)
      .css('padding-right', `${1 + Math.random() * 2}vh`); // slightly move it to avoid screen burning off. Padding-right because of the marquee
  }
}

function showNoEvents(message = 'No events found') {
  $('.event-name').text(message).animateCss('flash');
  $('#main-icon').removeClass().addClass('fas fa-tv');
  $('left-part-container').removeClass().animateCss('fadeInLeft');
  $('.event-details').animateCss('fadeInRight');

  $('#start').text('----');
  $('#start-time').text('----');
  $('#section').text('stay tuned!');
  $('#event-slide').show();

  setTimeout(pullData, 30000); // Retry in 30 seconds, just in case websockets went down...
}



/* End: Show Pictures */

/* Start: Menus */
function updateMenu(menu: Menu) {
  if (params['d'] === 'true') {
    logToDebug("Menu Update", menu);
    return;
  }
  if (!menuSlide) return;

  // Check if we had it originally
  if (!menuSlide.hasMenu(menu.id))
    return;

  menuSlide.deleteMenu(menu.id);
  menuSlide.addMenu(menu);

  afterDataUpdate();
}

/* End: Menus */

/**
 * @deprecated TODO: move to Slide
 * Fills in the given data in the weather slide
 */
function prepareWeatherSlide(data: WeatherData): void {
  const wRows = $('#weatherRows');
  wRows.html('');

  $('#placeName').text(data.city.name);
  const sunRise = new Date(data.city.sunrise * 1000);
  $('#sunRise time').text(
    getFormattedTime(sunRise.getHours(), sunRise.getMinutes()),
  );
  const sunSet = new Date(data.city.sunset * 1000);
  $('#sunSet time').text(
    getFormattedTime(sunSet.getHours(), sunSet.getMinutes()),
  );

  let currentDay = -1;
  for (let i = 0; i < data.list.length; i += 1) {
    const row = $('#templateWeather').clone();
    row.prop('id', `weather_${i}`);
    const entry = data.list[i];
    const infos = entry.main;
    const date = new Date(entry.dt * 1000);
    const day = date.getDay();
    if (i === 0) {
      currentDay = day;
      if (day === new Date().getDay()) {
        wRows.append($(`<h4>${_._('today', config.locale)}</h4>`));
      } else {
        wRows.append($(`<h4>${_._('tomorrow', config.locale)}</h4>`));
      }
    } else if (currentDay !== day) {
      currentDay = day;
      wRows.append($(`<h4>${_._('tomorrow', config.locale)}</h4>`));
    }
    const hours = date.getHours();
    row.find('.time').text(`${hours}:00`);
    // row.find(".weatherIcon img").attr("src", "https://openweathermap.org/img/wn/"+entry.weather[0].icon+"@2x.png");

    // Show the weather icon, animated or not
    row
      .find('.weatherIcon img')
      .attr('data-icon', entry.weather[0].icon)
      .attr(
        'src',
        `${config.base_root}img/amcharts_weather_icons/${config.use_animations ? 'animated' : 'static'}/${entry.weather[0].icon}.${config.use_animations ? 'svg' : 'png'}`,
      );

    row
      .find('.weatherTemperature span:first-of-type')
      .text(infos.temp.toFixed(1))
      .css('color', getTemperatureColor(infos.temp));
    row
      .find('.weatherTemperature span.temp_feels')
      .text(infos.feels_like.toFixed(1))
      .css('color', getTemperatureColor(infos.feels_like));
    row.find('.weatherDescr').text(entry.weather[0].description);
    row.find('.weatherCloud span:first-of-type').text(entry.clouds.all);
    if (i % 2) {
      row.addClass('even');
    }
    row.show();
    wRows.append(row);
  }
  weatherDataLastUpdate = getNow();
}

/**
 * Returns the #RGB string representing a color for the given temperature value
 */
function getTemperatureColor(celciusDegrees: number): string {
  if (celciusDegrees > 38) return '#c00001';
  if (celciusDegrees > 32) return '#cc0001';
  if (celciusDegrees > 27) return '#fe0002';
  if (celciusDegrees > 21) return '#f79649';
  if (celciusDegrees > 16) return '#ffc100';
  if (celciusDegrees > 10) return '#92d14f';
  if (celciusDegrees > 4) return '#00af50';
  if (celciusDegrees > -1) return '#0199fe';
  if (celciusDegrees > -7) return '#3432ff';
  if (celciusDegrees > -12) return '#7030a1';
  if (celciusDegrees > -18) return '#980299';
  return '#cb0298';
}

function loadKaraoke(): void {
  $.getJSON(import.meta.env.VITE_KARAOKE_API_URL as string)
    .fail((jqxhr, textStatus, error) => {
      // fail
      console.error('Cannot load karaoke.', error);
      isOffline = true;
    })
    .done((data /* , status, request */) => {
      isOffline = false;
      const $tbody = $('#karaoke tbody');
      $tbody.empty();
      let rD;
      try {
        rD = data?.sheets[0]?.data[0]?.rowData;
      } catch {
        console.error('Something went wrong with the API call');
        console.error(data);
        return;
      }
      if (rD && rD.length > 0) {
        let i = 0;
        rD.forEach((s) => {
          if (getStringFromValue(s.values[1]) === '') {
            return;
          }
          i += 1;
          $tbody.append(
            $(`<tr>
            <td>${i}</td>
            <td>${getStringFromValue(s.values[1])}</td>
            <td>${getStringFromValue(s.values[2])}</td>
            </tr>`),
          );
        });
      }
      $tbody.append(
        $(`<tr>
            <td>&gt;</td>
            <td>Request a song:</td>
            <td>https://goo.gl/pa9b6N</td>
            </tr>`),
      );
    });
}

function getStringFromValue(value): string {
  if (value && value.effectiveValue) {
    if (value.effectiveValue.stringValue) {
      return value.effectiveValue.stringValue;
    }
    if (value.effectiveValue.numberValue) {
      return value.effectiveValue.numberValue;
    }
  }
  return '';
}

/**
 * @deprecated
 */
function startKaraoke(): void {
  console.debug("[startKaraoke]");
  loadKaraoke();
  $('#karaoke')
    .show()
    .animateCss('zoomIn', () => undefined);
  setTimeout(stopKaraoke, 30000);
}

/**
 * @deprecated
 */
function stopKaraoke(): void {
  $('#karaoke').animateCss('rollOut', () => {
    $('#karaoke').hide();
    manager.nextSchedule();
    //evaluateEvents();
  });
}

/**
 * Callback for the click event
 */
function nextSlideClick(): void {
  manager.handleClick();
}

function updateTimeOffset(request: JQuery.jqXHR): void {
  const date = request.getResponseHeader('Date'); // Sat, 26 Mar 2022 10:19:53 GMT
  if (!date) {
    console.warn('Could not retrieve datetime from the server', request);
    timeOffset = 0;
    filter.updateTimeOffset(0);
  } else {
    timeOffset = dayjs(date.substr(0, 29)).diff(new Date());
    filter.updateTimeOffset(timeOffset);
    if (Number.isNaN(timeOffset)) {
      console.warn('Could not retrieve datetime from the server', request);
      timeOffset = 0;
      filter.updateTimeOffset(0);
    }
  }
  console.debug('[Time] Time offset:', timeOffset);
  updateClock();
}

async function sendStatsAsync() {
  const s = window.screen;
  const screen = {
    'screenWidth': s.width * window.devicePixelRatio,
    'screenHeight': s.height * window.devicePixelRatio,
    'availWidth': window.innerWidth,
    'availHeight': window.innerHeight,
    'colorDepth': s.colorDepth,
    'pixelDepth': s.pixelDepth,
    'orientation': s.orientation.type,
  };
  const navigation = JSON.stringify(window.performance.getEntriesByType('navigation')[0]);
  $.ajax({
    url: `${config.base_root}monitors/stats/${config.api_token}`,
    method: 'POST',
    data: {
      'screen': screen,
      'navigation': navigation,
      'timestamp': getNow().toISOString()
    },
    success(/*data, textStatus, request*/) {
      console.debug("screen statistics sent with success");
    },
    error(reason, xhr) {
      isOffline = true;
      console.error('sendStats failed:', reason);
      console.error(xhr);
    },
  });
}

function pullTimeDeltaFromServer(): void {
  $.ajax({
    url: `${config.base_root}status.json`,
    success(data, textStatus, request) {
      updateTimeOffset(request);
    },
    error(reason, xhr) {
      isOffline = true;
      console.error('getServerTime failed:', reason);
      console.error(xhr);
    },
  });
}

function initalizeSlides(): void {
  eventsSlide = new EventsSlide(manager, document.getElementById('event-slide') as HTMLDivElement);
  manager.registerSlide(eventsSlide, ScheduledSlideType.EVENTS);

  if (config.show_weather_forecast) {
    weatherForecastSlide = new WeatherForecastSlide(manager, document.getElementById('weather') as HTMLDivElement);
    manager.registerSlide(weatherForecastSlide, ScheduledSlideType.WEATHER);
  }

  if (config.show_pictures) {
    picsSlide = new PicsSlide(manager, document.getElementById('pics-container') as HTMLDivElement, config.base_root, config.api_token);
    manager.registerSlide(picsSlide, ScheduledSlideType.PICS);
  }

  if (config.show_videos) {
    vidsSlide = new VidsSlide(manager, document.getElementById('videos-container') as HTMLDivElement, config.base_root, config.api_token);
    manager.registerSlide(vidsSlide, ScheduledSlideType.VIDEOS);
  }

  if (config.show_menus) {
    menuSlide = new MenuSlide(manager, document.getElementById('menu-container') as HTMLDivElement);
    manager.registerSlide(menuSlide, ScheduledSlideType.MENUS);
  }

  if (config.show_orderslist) {
    ordersListSlide = new OrdersListSlide(manager, document.getElementById('orderslist-container') as HTMLDivElement);
    manager.registerSlide(ordersListSlide, ScheduledSlideType.ORDERSLIST);
  }

  // Interrup slides
  if (config.show_preparation_countdowns) {
    prepSlide = new PreparationSlide(manager, document.getElementById('preparations') as HTMLDivElement);
    manager.registerSlide(prepSlide, InterruptionSlides.PREPARATIONS);
  }

  if (config.show_we_are_closed_marketing) {
    marketingAfterSlide = new MarketingAfter(manager, document.getElementById('thirty-minutes-after') as HTMLDivElement);
    manager.registerSlide(marketingAfterSlide, InterruptionSlides.CLOSED);
  }

  if (config.show_happy_hours) {
    happyHourSlide = new HappyHourSlide(manager, document.getElementById('happy-hour-container') as HTMLDivElement);
    manager.registerSlide(happyHourSlide, InterruptionSlides.HAPPY_HOUR);
  }

  if (config.show_we_are_closing) {
    closingSlide = new ClosingSlide(manager, document.getElementById('final-15-minutes') as HTMLDivElement);
    manager.registerSlide(closingSlide, InterruptionSlides.CLOSING);
  }

  if (config.show_final_rounds) {
    lastCallSlide = new LastCallSlide(manager, document.getElementById('final-round-container') as HTMLDivElement);
    manager.registerSlide(lastCallSlide, InterruptionSlides.LAST_CALL);
  }
}

function startFromEvent(schedule: SchedulerItem, forceRefresh = false) {
  switch (schedule.subject) {
    case EventStatuses.preparation:
      if (prepSlide) {
        const e = getEventById(schedule.event_id);
        if (!e)
          throw new Error(`Event with ID ${schedule.event_id} not found`);
        prepSlide.initSlide(e.name, e.icon);
        prepSlide.setDeadline(e.startDate);
        manager.startSlide(prepSlide, forceRefresh);
      }
      break;
    case EventStatuses.happy_hour:
      if (happyHourSlide) {
        const e = getEventById(schedule.event_id);
        if (!e)
          throw new Error(`Event with ID ${schedule.event_id} not found`);
        happyHourSlide.initHappyHour(e.happy_hour);
        manager.startSlide(happyHourSlide, forceRefresh);
      }
      break;
    case EventStatuses.final_round:
      if (lastCallSlide) {
        const e = getEventById(schedule.event_id);
        if (!e)
          throw new Error(`Event with ID ${schedule.event_id} not found`);
        lastCallSlide.initSlide(e.endDate.subtract(15, 'minutes'));
        manager.startSlide(lastCallSlide, forceRefresh);
      }
      break;
    case EventStatuses.closing:
      if (closingSlide) {
        manager.startSlide(closingSlide, forceRefresh);
      }
      break;
    case EventStatuses.closed:
      if (marketingAfterSlide) {
        manager.startSlide(marketingAfterSlide, forceRefresh);
      }
      break;
    default:
      alert("Unsupported subject " + schedule.subject)
      break;
  }
}

function stopFromEvent(schedule: SchedulerItem) {
  switch (schedule.subject) {
    case EventStatuses.preparation:
      if (prepSlide) {
        manager.stopSlide(prepSlide);
      }
      break;
    case EventStatuses.happy_hour:
      if (happyHourSlide) {
        manager.stopSlide(happyHourSlide);
      }
      break;
    case EventStatuses.final_round:
      if (lastCallSlide) {
        manager.stopSlide(lastCallSlide);
      }
      break;
    case EventStatuses.closing:
      if (closingSlide) {
        manager.stopSlide(closingSlide);
      }
      break;
    case EventStatuses.closed:
      if (marketingAfterSlide) {
        manager.stopSlide(marketingAfterSlide);
      }
      break;
    default:
      alert("Unsupported subject " + schedule.subject)
      break;
  }
}

/**
 * This is the file entry point, the function that get called first, after the global variables initialization
 */
function init(): void {
  if (params['d'] === 'true') {
    showDebugPage();
  }
  document.title = `${import.meta.env.VITE_BRAND_NAME} - ${config.name}`;
  $.ajaxSetup({
    cache: false,
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });

  if (params['d'] !== 'true') {
    initalizeSlides();
  }

  timeOffset = 0;
  pullTimeDeltaFromServer();

  updateClock();
  setInterval(updateClock, 9000);

  if (!config.use_animations) {
    removeAnimations();
  }


  if (params['d'] !== 'true') {
    // Set up scheduler
    // schedulerWorker = new SchedulerWorker();
    schedulerWorker = workaroundWorker(schedulerWorkerBlob, { name: 'schedulerWorker' });
    //schedulerWorker = new Worker(schedulerWorkerURL);

    schedulerWorker.addEventListener("message", (d: { data: { schedule: SchedulerItem, reason: ScheduleReason, dayChange?: boolean } }) => {
      if (d.data.schedule) {
        console.log("Schedule event!", d.data);
        const reason: ScheduleReason = d.data.reason;
        switch (reason) {
          case ScheduleReason.refresh:
            console.log("Refreshing event")
            startFromEvent(d.data.schedule, true);
            break;
          case ScheduleReason.started:
          case ScheduleReason.running: // todo: maybe running is not needed
            console.log("Starting or running event")
            startFromEvent(d.data.schedule);
            break;
          case ScheduleReason.ended:
            console.log("Event ended")
            stopFromEvent(d.data.schedule);
            manager.moveToNextSchedule();
            break;
          default:
            alert("Unknown schedule reason: " + reason);
        }
        //evaluateEvents();
      } else if (d.data.dayChange) {
        // data has changed, let's pull new data from the server (events repeating on a certain day might change)
        console.log("date change detected, pulling");
        pullData();
      }
    });


    schedulerWorker.postMessage({
      action: SchedulerActions.startProcessing,
      config: {
        show_happy_hours: config.show_happy_hours,
        show_preparation_countdowns: config.show_preparation_countdowns,
        show_final_rounds: config.show_final_rounds,
        show_we_are_closing: config.show_we_are_closing,
        show_we_are_closed_marketing: config.show_we_are_closed_marketing
      }
    });
  }

  if (config.show_videos) {
    loadingMessage('getting ready for videos…');
    console.debug('Initializing videos Worker…');
    if (!window.Worker) {
      console.error('Workers are not supported');
      fatalError('unfortunately, this browser is not supported😢. Workers are required.');
    }

    //videosWorker = new Worker(videosWorkerURL);
    //videosWorker = new VideosWorker();
    videosWorker = workaroundWorker(videosWorkerBlob, { name: 'videosWorker' });
    videosWorker.postMessage({ action: 'API', key: config.api_token });
    videosWorker.postMessage({ action: 'BASEPATH', path: config.base_root });
  }

  isOffline = false;
  animEnd = (function getBrowserSpecificEvent(el) {
    const animations = {
      animation: 'animationend',
      OAnimation: 'oAnimationEnd',
      MozAnimation: 'mozAnimationEnd',
      WebkitAnimation: 'webkitAnimationEnd',
    };

    const res = Object.keys(animations).find((t) => el.style[t] !== undefined);
    if (res) {
      return animations[res];
    }
    return undefined;
  }(document.createElement('fakeelement')));
  if (!animEnd) {
    console.error('AnimationEnd event not found on this browser');
  }

  $('body').on('click', nextSlideClick);

  loadingMessage('setting up connections…');

  const channelSuffix = config.channel_hash ? `-${config.channel_hash}` : '';

  window.Echo.channel(`data-updates-${config.realm_id}${channelSuffix}`)
    .listen('.item.deleted', (el) => {
      console.log('[WS] ITEM DELETED');
      console.dir(el);
      handleItemDeleted(el);
    })
    .listen('.item.created', (el) => {
      console.log('[WS] ITEM CREATED');
      console.dir(el);
      handleItemCreated(el);
    })
    .listen('.item.updated', (el) => {
      console.log('[WS] ITEM UPDATED');
      console.dir(el);
      handleItemUpdated(el);
    });

  window.Echo.channel(`weather-updates-${config.realm_id}${channelSuffix}`)
    .listen('.w.updated', (response) => {
      console.log('[WS] WEATHER UPDATED');
      console.dir(response);
      prepareWeatherSlide(response.data);
      weatherOffline = false;
    });
  //alert(`alerts-${config.realm_id}`);
  window.Echo.channel(`alerts-${config.realm_id}${channelSuffix}`)
    .listen('.alert.created', (response) => {
      console.log('[AL] Alert received');
      console.dir(response);
      handleAlertData(response.data);
    });
  window.Echo.channel(`orderslist-updates-${config.realm_id}${channelSuffix}`)
    .listen('.ol.updated', (response) => {
      console.log('[OL] Orderslist received');
      console.dir(response);
      handleOrderslistData(response.data);
    });

  window.Echo.connector.pusher.connection.bind('state_change', (states: { current: ConnectionStatus }) => {
    // https://pusher.com/docs/channels/using_channels/connection/#available-states
    isWsOffline = states.current !== 'connected';
  });
  // socketID = window.Echo.socketId();

  if (import.meta.env.VITE_APP_ENV === 'local') {
    window["pullData"] = pullData;
    window["e"] = e;
    window["config"] = config;
    window["intervals"] = intervalsObject;
    window["testAlert"] = handleAlertData;
  }


  if (params['d'] !== 'true') {
    loadingMessage('processing data…');
  }
  updateData(data);

  const observer = new PerformanceObserver((list) => {
    list.getEntries().forEach((entry) => {
      // When the last events has completed, we can measure all times
      if (entry['loadEventEnd'] > 0) {
        sendStatsAsync();
        stopLoading();
      }
    });
  });

  observer.observe({ type: "navigation", buffered: true });


  if (params['d'] !== 'true') {
    // Start the slideshow();
    manager.start();

    if (params["s"]?.length > 0) {
      switch (params["s"]) {
        case 'v':
          manager.skipScheduleTo(ScheduledSlideType.VIDEOS);
          break;
        case 'p':
          manager.skipScheduleTo(ScheduledSlideType.PICS);
          break;
        case 'w':
          manager.skipScheduleTo(ScheduledSlideType.WEATHER);
          break;
        case 'm':
          manager.skipScheduleTo(ScheduledSlideType.MENUS);
          break;
        case 'e':
          manager.skipScheduleTo(ScheduledSlideType.EVENTS);
          break;
        case 'o':
          manager.skipScheduleTo(ScheduledSlideType.ORDERSLIST);
          break;
        case 'k':
          manager.skipScheduleTo(ScheduledSlideType.KARAOKE);
          break;
        default:
        //
      }
    }
  }
}

function appendEvent(/*event: AdsEvent*/): void {
  pullData();
}

function deleteEvent(/*event: AdsEvent*/): void {
  pullData();
}

function updateEvent(/*event: AdsEvent*/): void {
  // check if this element is present.
  pullData();
  // TODO: problem: the order is wrong (gets shown as first slide)
  /*   const index = e.findIndex((el) => el.id === event.id);
    if (index !== -1) {
      e[index] = computeStartAndEndDates(event);
      evaluateEvents();
    } else {
      pullData();
    } */
}

function handleItemCreated(d): void {
  switch (d?.what) {
    case 'm':
    case 'menu':
      return; // Another monitor/menu was created, we don't care
    case 'e':
    case 'es':
      appendEvent(/*d.data*/);
      break;
    case 'ps':
    case 'vs':
    case 'hh':
      pullData();
      break;
    default:
      console.warn(`unknown key ${d?.what}`);
  }
}

function handleItemDeleted(d): void {
  console.debug(d);
  switch (d?.what) {
    case 'm':
      if (d?.data?.id === config.id) {
        fatalError('This monitor has been deleted, adieu!');
      }
      break;
    case 'e':
    case 'es':
      deleteEvent(/*d.data*/);
      break;
    case 'menu':
      menuSlide?.deleteMenu(d.data.id);
      break;
    case 'ps':
    case 'vs':
    case 'hh':
      pullData();
      break;
    default:
      console.warn(`unknown key ${d?.what}`);
  }
}

function handleItemUpdated(d): void {
  console.debug(d);
  switch (d?.what) {
    case 'm':
      if (d?.data?.id === config.id) {
        reloadPage();
      }
      break;
    case 'e':
    case 'es':
      updateEvent(/*d.data*/);
      break;
    case 'menu':
      updateMenu(d.data);
      break;
    case 'ps':
    case 'vs':
    case 'hh':
      pullData();
      break;
    default:
      console.warn(`unknown key ${d?.what}`);
  }
}

/**
 * @deprecated, use the one from misc.
 * @param el 
 */
function autosizeText(el: HTMLElement): void {
  //Restore the maximum font size:
  let fontSize = 300;
  $(el).css('font-size', `${fontSize}px`);
  let steps = 1;
  // if it doesn't fit, divide it by 2
  while (el.scrollHeight > el.offsetHeight || el.scrollWidth > el.offsetWidth) {
    fontSize = ~~(fontSize / 2);
    $(el).css('font-size', `${fontSize}px`);
    console.log(steps++, fontSize);
  }
  // If it was divided at least once
  if (steps > 1) {
    // Increase the font by 3 until it doesn't fit again
    while (el.scrollHeight <= el.offsetHeight && el.scrollWidth <= el.offsetWidth) {
      fontSize = fontSize + 3;
      $(el).css('font-size', `${fontSize}px`);
      console.log(steps++, fontSize);
    }
    // Make it fit again by removing 1
    while (el.scrollHeight > el.offsetHeight || el.scrollWidth > el.offsetWidth) {
      fontSize = fontSize - 1;
      $(el).css('font-size', `${fontSize}px`);
      console.log(steps++, fontSize);
    }
  }
}


/* Start: Alerts */

const $alert = $('#alert');
let ninaAlertsBuffer: NinaAlert[] = [];
let ninaDebounceTimer: ReturnType<typeof setTimeout> | null = null;

function getSingleAlertElements() {
  return {
    $title: $('#alert-title'),
    $message: $('#alert-message'),
    alertQrCanvas: document.getElementById('alert-qr') as HTMLCanvasElement
  };
}

function restoreSingleAlertDOM() {
  if ($alert.hasClass('multi-alert')) {
    $alert.removeClass('multi-alert');
    // Rebuild the standard DOM structure for single alert
    $alert.html(`
    <i id="alert-icon" class="fas fa-circle-info"></i>
    <div class="row">
        <div class="col">
            <h4 id="alert-title"></h4>
        </div>
        <div class="col-auto">
            <canvas id="alert-qr"></canvas>
        </div>
    </div>
    <div>
        <div id="alert-message"></div>
    </div>
    `);
  }
}

function resetAlert(): void {
  const { $title, $message, alertQrCanvas } = getSingleAlertElements();
  if ($message.length) $message.removeClass('nina');
  if ($title.length) $title.removeClass('nina');
  if (alertQrCanvas) alertQrCanvas.style.display = "none";
}

function handleOrderslistData(data: OrderslistData) {
  console.dir(data);
  if (ordersListSlide) {
    ordersListSlide.updateData(data);
    if (data.important) {
      manager.skipScheduleTo(ScheduledSlideType.ORDERSLIST);
    }
  }
}

function handleAlertData(responseData: NinaAlert): void {
  if (params['d'] === 'true') {
    logToDebug("Alert Data", responseData);
    return;
  }
  if (responseData?.source === "NINA") {
    ninaAlertsBuffer.push(responseData);
    if (ninaDebounceTimer) {
      clearTimeout(ninaDebounceTimer);
    }
    // Collect alerts for 2 seconds (debounce)
    ninaDebounceTimer = setTimeout(processNinaAlerts, 2000);
  } else {
    // Normal alert - ensure DOM is correct then display
    restoreSingleAlertDOM();
    prepareAlert(responseData);
    displayAlert(responseData);
  }
}

function formatAlertDuration(startStr?: string, endStr?: string): string {
  if (!startStr && !endStr) return "";

  const start = startStr ? dayjs(startStr) : null;
  const end = endStr ? dayjs(endStr) : null;

  if (start && !start.isValid()) return "";
  if (end && !end.isValid()) return "";

  const now = dayjs();
  const isDe = config.locale === 'de';

  const t = {
    today: isDe ? 'Heute' : 'Today',
    tomorrow: isDe ? 'morgen' : 'tomorrow',
    until: isDe ? 'bis' : 'until',
    from: isDe ? 'ab' : 'from'
  };

  const getFormat = (d: dayjs.Dayjs) => {
    // If date is more than 6 days away, show Date instead of Weekday to avoid confusion
    // diff returns milliseconds by default if no unit passed, or truncated unit.
    // 'day' diff ignores time, measuring from start of day if logic requires, but simply:
    if (d.diff(now, 'day') > 6) {
      return 'D.M. LT';
    }
    return 'dd LT';
  };

  // If we have an end date
  if (end) {
    // If start is missing or in the past (active)
    if (!start || start.isBefore(now)) {
      if (end.isSame(now, 'day')) {
        return `${t.today} ${t.until} ${end.format('LT')}`;
      } else if (end.isSame(now.add(1, 'day'), 'day')) {
        return `${t.until} ${t.tomorrow} ${end.format('LT')}`;
      } else {
        return `${t.until} ${end.format(getFormat(end))}`;
      }
    }

    // Start in future
    if (start && start.isSame(now, 'day')) {
      if (end.isSame(now, 'day')) {
        return `${t.today} ${start.format('LT')} ${t.until} ${end.format('LT')}`;
      } else {
        return `${t.today} ${start.format('LT')} ${t.until} ${end.format(getFormat(end))}`;
      }
    }

    // Start is tomorrow
    if (start && start.isSame(now.add(1, 'day'), 'day')) {
      return `${t.tomorrow} ${start.format('LT')} - ${end.format(getFormat(end))}`;
    }

    // Start later
    if (start) return `${start.format(getFormat(start))} - ${end.format(getFormat(end))}`;
  }

  // Only start (no end)
  if (start) {
    if (start.isSame(now, 'day')) return `${t.from} ${start.format('LT')}`;
    if (start.isSame(now.add(1, 'day'), 'day')) return `${t.from} ${t.tomorrow} ${start.format('LT')}`;
    return `${t.from} ${start.format(getFormat(start))}`;
  }

  return "";
}

function processNinaAlerts() {
  const alerts = [...ninaAlertsBuffer];
  ninaAlertsBuffer = []; // Clear buffer
  ninaDebounceTimer = null;

  if (alerts.length === 0) return;

  // Sort by level (Catastrophy > Warning > Error > Info) // Assuming enum order 3>2>1>0
  alerts.sort((a, b) => b.level - a.level);

  // Take top 4
  const toShow = alerts.slice(0, 4);

  if (toShow.length === 1) {
    restoreSingleAlertDOM();
    prepareNinaAlert(toShow[0]);
    displayAlert(toShow[0]);
  } else {
    displayMultipleNinaAlerts(toShow);
  }
}

function displayMultipleNinaAlerts(alerts: NinaAlert[]) {
  console.debug("Showing multiple NINA alerts", alerts);

  if (alertTimeout) {
    clearTimeout(alertTimeout);
    alertTimeout = null;
  }

  $alert.empty();
  $alert.addClass('multi-alert');
  $alert.css("background-color", "#00264d");

  // Container for multiple alerts
  const container = $('<div class="multiple-alerts-container" style="display: flex; flex-direction: column; gap: 1.5rem; height: 100%; justify-content: center; padding: 2rem;"></div>');

  alerts.forEach((alert, index) => {
    const isOdd = index % 2 !== 0; // Alternating
    let titleText = "";
    if (config.locale === 'de') {
      titleText = alert.title;
    } else {
      titleText = alert.title_en ? alert.title_en : alert.title;
    }

    const timeText = formatAlertDuration(alert.start, alert.end);

    // Colors matching single-alert/full-screen-slide styles
    let bg = 'rgba(255,255,255,0.1)';
    let textColor = '#ffffff';
    let iconColor = '#ffffff';

    if (alert.level === AlertLevel.Catastrophy) {
      bg = '#990000';
      textColor = '#f0ec27';
      iconColor = '#f0ec27';
    } else if (alert.level === AlertLevel.Warning) {
      bg = '#ef740a';
      textColor = '#153a67';
      iconColor = '#153a67';
    }

    const iconHtml = `
      <div class="icon-col" style="margin: 0 1.5rem; font-size: 3.5em; color: ${iconColor}">
          <i class="fas fa-${levelToIcon(alert.level)}"></i>
      </div>`;

    const textHtml = `
      <div class="text-col" style="flex: 1; text-align: ${isOdd ? 'right' : 'left'};">
          <h4 style="margin: 0; color: ${textColor}; font-size: 3rem; line-height: 1.1;">${titleText}</h4>
          ${timeText ? `<div style="font-size: 1.5rem; color: ${textColor}; opacity: 0.9; margin-top: 0.2rem;">${timeText}</div>` : ''}
      </div>`;

    const qrHtml = `
      <div class="qr-col" style="margin: 0 1rem; background: #fff; padding: 5px; border-radius: 4px;">
          <canvas id="multi-alert-qr-${index}" style="width: 120px; height: 120px; display: block;"></canvas>
      </div>`;

    // Alternating layout
    let innerContent = '';
    if (isOdd) {
      // QR Left | Text Right | Icon Right
      innerContent = qrHtml + textHtml + iconHtml;
    } else {
      // Icon Left | Text Left | QR Right
      innerContent = iconHtml + textHtml + qrHtml;
    }

    const alertRow = $(`
      <div class="alert-row" style="background: ${bg}; padding: 1rem; border-radius: 12px; display: flex; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); min-height: 160px;">
         ${innerContent}
      </div>
    `);

    container.append(alertRow);
  });

  $alert.append(container);
  $alert.show();
  $alert.animateCss('zoomInDown');

  // Generate QR codes
  alerts.forEach((alert, index) => {
    const canvas = document.getElementById(`multi-alert-qr-${index}`) as HTMLCanvasElement;
    if (canvas && alert.url) {
      generateQR(canvas, alert.url);
    }
  });

  // Calculate timeout - maybe slightly longer for reading multiple items
  const maxTimeout = 30;
  alertTimeout = setTimeout(hideAlert, maxTimeout * 1000);
}

function getLevelColor(l: AlertLevel): string {
  switch (l) {
    case AlertLevel.Info:
      return "#ffffff";
    case AlertLevel.Catastrophy:
      return "#f0ec27";
    case AlertLevel.Error:
      return "#ff0000";
    case AlertLevel.Warning:
      return "#ffffff"; // Keep icon white on orange bg? Or match text?
    default:
      return "#ffffff";
  }
}

function generateQR(canvas: HTMLCanvasElement, text: string) {
  const options: QR.QRCodeRenderersOptions = {
    errorCorrectionLevel: 'L',
    margin: 1.5,
    color: {
      dark: '#000000',
      light: '#ffffff',
    },
  };
  QR.toCanvas(canvas, text, options, (error) => {
    if (error) console.error(error);
  });
}

function prepareNinaAlert(alert: NinaAlert): void {
  resetAlert();
  const { $title, $message } = getSingleAlertElements();
  $message.addClass('nina');
  $title.addClass('nina');

  let message = "";
  const timeText = formatAlertDuration(alert.start, alert.end);
  if (timeText) {
    message += timeText + '<br>';
  }

  if (config.locale === 'de') {
    message += alert.message;
    setAlertTitle("NINA: " + alert.title);
  } else {
    message += alert.message_en ? alert.message_en : alert.message;
    setAlertTitle("NINA: " + (alert.title_en ? alert.title_en : alert.title));
  }
  if (message.length > 500) {
    // Ensure we don't cut in the middle of a word
    const cutOff = message.lastIndexOf(' ', 550);
    message = message.substring(0, cutOff === -1 ? 550 : cutOff) + '…';
    if (config.locale === 'de') {
      message += `<br><ul><li><em>Für weitere Informationen nutzen Sie bitte den QR-Code.</em></li></ul>`;
    } else {

      message += `<br><ul><li><em>For further information, please scan the QR code.</em></li></ul>`;
    }
  }
  setAlertMessage(message, true);
  setAlertLink(alert.url);

}

function setAlertLink(link: string): void {
  const { alertQrCanvas } = getSingleAlertElements();
  if (link && link.length > 0 && alertQrCanvas) {
    generateQR(alertQrCanvas, link);
    alertQrCanvas.style.display = 'block';
  }
}

function prepareAlert(alert: Alert): void {
  resetAlert();
  setAlertMessage(alert.message, false);
  setAlertLink(alert.url);
  setAlertTitle(alert.title);
}

function setAlertMessage(content: string, isHTML = false): void {
  const { $message } = getSingleAlertElements();
  if (isHTML) {
    $message.html(content);
  } else {
    fillInComponentSafe($message[0], content);
  }
  // do not run autosizeText($message[0]) here, it is not working if the text is not visible yet
}

function setAlertTitle(content: string): void {
  const { $title } = getSingleAlertElements();
  fillInComponentSafe($title[0], content);
  // do not run autosizeText($title[0]) here, it is not working if the text is not visible yet
}

function displayAlert(alert: Alert): void {
  console.debug("Showing an alert");

  if (alertTimeout) {
    clearTimeout(alertTimeout);
    alertTimeout = null;
  }

  const { $title } = getSingleAlertElements();

  $alert.css("background-color", "#00264d");
  $title.css("color", "");

  // Set the icon based on the level
  $('#alert-icon').removeClass().addClass(`fas fa-${levelToIcon(alert?.level)}`);

  if (alert?.level === AlertLevel.Catastrophy) {
    $title.css("color", "#f0ec27");
    $alert.css("background-color", "#990000");
  } else if (alert?.level === AlertLevel.Warning) {
    $title.css("color", "#153a67");
    $alert.css("background-color", "#ef740a");
  }


  $alert.show();
  //These must be executed when the text is already visible
  const { $message } = getSingleAlertElements();
  if ($title.length) autosizeText($title[0]);
  if ($message.length) autosizeText($message[0]);
  $alert.animateCss('zoomInDown');


  alertTimeout = setTimeout(hideAlert, (alert?.timeoutInSeconds ?? 30) * 1000);
}

function levelToIcon(l: AlertLevel): string {
  switch (l) {
    case AlertLevel.Info:
      return "circle-info";
    case AlertLevel.Catastrophy:
      return "skull-crossbones";
    case AlertLevel.Error:
      return "square-xmark";
    case AlertLevel.Warning:
      return "triangle-exclamation";
    default:
      return "circle-info";
  }
}

function hideAlert(): void {
  console.debug("Hide alert");
  $('#alert').animateCss('slideOutDown', () => {
    $('#alert').hide();
  });
}

// bootstrap
window.addEventListener('DOMContentLoaded', () => {
  init();
});

/* Debug Mode Helpers */

function renderDebugValue(val: any): HTMLElement {
  if (typeof val === 'object' && val !== null) {
    return renderDebugTree(val);
  }
  const span = document.createElement('span');
  span.textContent = String(val);
  if (typeof val === 'string') span.style.color = '#d63384'; // pink
  else if (typeof val === 'number') span.style.color = '#fd7e14'; // orange
  else if (typeof val === 'boolean') span.style.color = '#0d6efd'; // blue
  return span;
}

function renderDebugTree(obj: any): HTMLElement {
  const container = document.createElement('div');

  const keys = Object.keys(obj).filter(key =>
    !['user_id', 'created_at', 'updated_at', 'deleted_at', 'pivot'].includes(key)
  ).sort((a, b) => {
    // Put primitive values first
    const aVal = obj[a];
    const bVal = obj[b];
    const aIsObj = typeof aVal === 'object' && aVal !== null;
    const bIsObj = typeof bVal === 'object' && bVal !== null;

    if (aIsObj !== bIsObj) {
      // primitives first (isObj = false before true)
      return aIsObj ? 1 : -1;
    }

    // Use numeric sort for keys so "10" comes after "2"
    return a.localeCompare(b, undefined, { numeric: true, sensitivity: 'base' });
  });

  for (const key of keys) {
    const value = obj[key];
    const row = document.createElement('div');
    row.style.marginBottom = '4px';
    row.style.lineHeight = '1.4';

    if (typeof value === 'object' && value !== null) {
      const details = document.createElement('details');
      details.style.marginBottom = '5px';

      const summary = document.createElement('summary');
      summary.style.cursor = 'pointer';
      summary.style.fontWeight = '600';
      summary.style.color = '#333';

      // Try to find a display name
      let displayName = '';
      if (!Array.isArray(value)) {
        // Check common name properties
        if (value.name) displayName = value.name;
        else if (value.title) displayName = value.title;
        else if (value.id) displayName = '#' + value.id;
      }

      // If we have a name, use it. Otherwise fall back to Object/Array type info
      let typeLabel;
      if (Array.isArray(value)) {
        typeLabel = `Array[${value.length}]`;
      } else {
        typeLabel = displayName ? displayName : 'Object';
      }

      summary.innerHTML = `${key} <span style="font-weight:normal; color:#888; font-size: 0.9em">(${typeLabel})</span>`;

      details.appendChild(summary);

      const content = renderDebugTree(value);
      content.style.paddingLeft = '15px';
      content.style.marginTop = '4px';
      content.style.borderLeft = '2px solid #eee';

      details.appendChild(content);
      row.appendChild(details);
    } else {
      const keySpan = document.createElement('span');
      keySpan.textContent = key + ': ';
      keySpan.style.color = '#555';
      keySpan.style.fontWeight = '500';

      row.appendChild(keySpan);
      row.appendChild(renderDebugValue(value));
    }
    container.appendChild(row);
  }
  return container;
}

function showDebugPage() {
  document.body.innerHTML = `
          <div id="debug-container" style="background: white; color: black; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; height: 100vh; overflow: auto;">
            <h1 style="border-bottom: 2px solid #333; padding-bottom: 10px; margin-top: 0;">Monitor Debug Mode</h1>
              <div id="debug-controls" style="margin-bottom: 20px; padding: 10px; background: #eee; border-radius: 4px;">
                <strong>Controls: </strong>
              </div>
              <div style="display: flex; gap: 20px; height: calc(100vh - 150px);">
                <div style="flex: 1; display: flex; flex-direction: column; border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">
                  <h2 style="margin: 0; padding: 10px; background: #f0f0f0; border-bottom: 1px solid #ccc; font-size: 1.2em;">Configuration</h2>
                  <div id="debug-config" style="flex: 1; padding: 15px; overflow: auto; background: #fff;"></div>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; border: 1px solid #ccc; border-radius: 4px; overflow: hidden;">
                  <h2 style="margin: 0; padding: 10px; background: #f0f0f0; border-bottom: 1px solid #ccc; font-size: 1.2em;">Logs & Data Events</h2>
                  <div id="debug-logs" style="flex: 1; padding: 10px; overflow: auto; background: #fff; font-family: monospace;"></div>
                </div>
              </div>
          </div>
  `;

  const configEl = document.getElementById('debug-config');

  if (configEl) {
    configEl.appendChild(renderDebugTree(config));
  }

  const btn = document.createElement('button');
  btn.textContent = "Force Pull Data";
  btn.style.padding = "5px 10px";
  btn.style.cursor = "pointer";
  btn.onclick = () => pullData();
  document.getElementById('debug-controls')?.appendChild(btn);
}

function logToDebug(title: string, obj: any) {
  const logs = document.getElementById('debug-logs');
  if (!logs) return;

  const entry = document.createElement('div');
  entry.style.borderBottom = "1px solid #eee";
  entry.style.padding = "8px";
  entry.style.marginBottom = "5px";
  entry.style.background = "#fafafa";

  const time = new Date().toLocaleTimeString();
  entry.innerHTML = `<div style="font-weight: bold; color: #2c3e50; margin-bottom: 4px;">[${time}] ${title}</div>`;

  if (obj !== undefined) {
    const details = document.createElement('details');
    const summary = document.createElement('summary');
    summary.textContent = "View Data Payload";
    summary.style.cursor = "pointer";
    summary.style.color = "#007bff";
    summary.style.fontSize = "0.9em";

    details.appendChild(summary);

    const content = renderDebugTree(obj);
    content.style.marginTop = "5px";
    content.style.padding = "10px";
    content.style.background = "#fff";
    content.style.border = "1px solid #eee";
    content.style.borderRadius = "4px";

    details.appendChild(content);
    entry.appendChild(details);

    if (title === "Initial Data" || title === "Dynamic Data Update") {
      details.open = true;
    }
  }

  logs.prepend(entry);
}
