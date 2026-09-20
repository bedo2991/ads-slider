
import dayjs from 'dayjs/esm/index.js'

export as namespace AdsTypes;

export interface Picture {
  id: number;
  bg_color: string;
  color: string;
  clock_location: number;
  duration: number;
}

export interface Video {
  id: number;
  bg_color: string;
  color: string;
  clock_location: number;
}

export interface PictureSlide extends ElementWithRealStartDate {
  scheduleable_id: number;
  scheduleable: Picture;
}

export interface VideoSlide extends ElementWithRealStartDate {
  end: string;
  start: string;
  scheduleable: Video;
  scheduleable_id: number;
}

export interface ElementWithRealStartDate {
  real_start_date: string;
  real_end_date: string;
  start_time: string;
  end_time: string;
  startDate: dayjs.Dayjs;
  endDate: dayjs.Dayjs;
  repeat: string | null;
  start: string | null;
  end: string | null;
}

export interface Monitor {
  id: number;
  name: string;
  events_to_show: number;
  show_preparation_countdowns: boolean;
  show_final_rounds: boolean;
  show_we_are_closing: boolean;
  show_we_are_closed_marketing: boolean;
  show_cancelled_events: boolean;
  show_menus: boolean;
  show_happy_hours: boolean;
  show_pictures: boolean;
  show_videos: boolean;
  show_karaoke: boolean;
  show_weather_forecast: boolean;
  show_orderslist: boolean;
  use_animations: boolean;
  show_marquee: boolean;
  show_event_while_is_happening: boolean;
  realm_id: number;
  api_token: string;
  channel_hash?: string;
}

export interface Config extends Monitor {
  id: number,
  name: string,
  locale: string;
  events_to_show: number,
  base_root: string,
  debug_level: string, // debug, warning, info, debug
  channel_hash?: string;
}

export interface HappyHour {
  price: string;
  drink: string;
  info: string;
  start: string;
  end: string;
}

export interface Product {
  name: string;
  price: string;
  size: string;
  special?: boolean;
  disabled?: boolean;
  price2?: string;
  size2?: string;
}

export interface Menu {
  id: number;
  currency: string;
  category_name: string;
  icon: string;
  products: Product[];
}

export interface MenuSimple {
  id: number;
  monitors: [{ id: number }];
}

export interface AdsEvent extends ElementWithRealStartDate {
  id: number;
  cancelled: boolean;
  color: string;
  disabled: boolean;
  final_round_confirmed: boolean;
  happy_hour: HappyHour | null;
  icon: string;
  is_karaoke: boolean;
  link: string | null;
  marquee: string | null;
  menus: MenuSimple[] | null;
  name: string;
  not_closing: boolean;
  place: string;
  preparation_time: number | null;
}

export interface City {
  id: number;
  name: string;
  coord: { lon: number; lat: number };
  country: string;
  population: number;
  timezone: number; // Shift in seconds from UTC
  sunrise: number; // Sunrise time, unix, UTC
  sunset: number; // Sunset time, unix, UTC
}

export interface Weather {
  id: number;
  main: string;
  description: string;
  icon: string;
}

export interface Forecast {
  dt: number;
  main: {
    temp: number;
    feels_like: number;
    pressure: number; // Pressure in hPa
    humidity: number; // in %
    temp_min: number; // Unit Default: Kelvin, Metric: Celsius, Imperial: Fahrenheit.
    temp_max: number;
    sea_level: number; // Pressure in hPa at sea_level
    grnd_level: number; // Pressure in hPa at grnd_level} & {temp_kf: number};
  }
  weather: Weather[];
  clouds: { all: number };
  wind: {
    speed: number; // Wind speed. Unit Default: meter/sec, Metric: meter/sec, Imperial: miles/hour.
    deg: number; // Wind direction, degrees (meteorological)
    gust?: number; // Wind gust. Unit Default: meter/sec, Metric: meter/sec, Imperial: miles/hour.
  };
  visibility: number;
  pop: number;
  rain?: { '3h': number }; // Only in the first Element when received in a Forecast list
  snow?: { '3h': number }; // Only in the first Element when received in a Forecast list
  sys: { pod: 'd' | 'n' };
  dt_txt: string; // Time of data forecasted, ISO, UTC
}

export interface WeatherData {
  cod: string;
  message: number;
  cnt: number;
  list: Forecast[];
  city: City;
}

export interface InitialServerData extends ServerData {
  m: Monitor;
  locale: string;
}

export interface LatestTimestamps {
  e: string;
  men: string;
  mon: string;
  p: string;
  ps: string;
  v: string;
  version: string;
  vs: string;
}

export interface ServerData {
  version: string;
  menus: Menu[];
  latest: LatestTimestamps;
  e: AdsEvent[];
  p: PictureSlide[];
  v: VideoSlide[];
  ol: OrderslistData;
  weather: WeatherData;
}

export interface OrderslistItem {
  label: string | null,
  timestamp: string
  status?: "new" | "mod" | "del"
}

export interface LabelsValues {
  label: string;
  value: string | number;
}

export interface OrderslistData {
  important: boolean;
  pending_title?: string;
  fulfilled_title?: string;
  pickup_title?: string;
  pending: OrderslistItem[];
  fulfilled: OrderslistItem[];
  pickup: OrderslistItem[];
  counters: LabelsValues[];
}

export interface OrdersListDataWihthTimestamp extends OrderslistData {
  timestamp: string;
}