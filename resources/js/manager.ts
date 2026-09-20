import { Slide, SlideState } from "./slides/Slide.js";
import { Config } from './types.js';
import { Mediator } from './patterns/Mediator.js';
import { Sema } from 'async-sema';

export enum ScheduledSlideType {
    EVENTS = "EVENTS",
    PICS = "PICS",
    VIDEOS = "VIDEOS",
    WEATHER = "WEATHER",
    MENUS = "MENUS",
    KARAOKE = "KARAOKE",
    ORDERSLIST = "ORDERSLIST",
}

export enum InterruptionSlides {
    HAPPY_HOUR = "HAPPY_HOUR",
    PREPARATIONS = "PREPARATIONS",
    LAST_CALL = "LAST_CALL",
    CLOSING = "CLOSING",
    CLOSED = "CLOSED"
}

export type SlideType = ScheduledSlideType | InterruptionSlides;

export enum SlideEvents {
    DONE = "DONE", // the slide has finished running (e.g. timeout of the weather slide)
    PAGE_END = "PAGE_END", // e.g the event slide has finished running, showing another one
    CYCLE_END = "CYCLE_END", // same as page_end, but it was also the end of the cycle.
    NOTHING = "NOTHING", // the slide had no data to show
    DATA_UPDATED = "DATA_UPDATED", // the data has been updated and the slide needs to refresh
    ERROR = "ERROR" // an error occurred, skip to the next slide
}

export class Manager implements Mediator {

    private static _instance: Manager;
    private conf: Config | null = null;
    private currentSlideSemaphore = new Sema(1);

    private currentSlide: Slide | null = null;
    private slides = new Map<SlideType, Slide>();
    private slideHadNoData = new Map<Slide, boolean>();
    private halt = false;
    private scheduleIndex = 0;
    private clock: HTMLDivElement;

    private schedule: ScheduledSlideType[] =
        [ScheduledSlideType.WEATHER,
        ScheduledSlideType.ORDERSLIST,
        ScheduledSlideType.EVENTS,
        ScheduledSlideType.ORDERSLIST,
        ScheduledSlideType.MENUS,
        ScheduledSlideType.EVENTS,
        ScheduledSlideType.ORDERSLIST,
        ScheduledSlideType.PICS,
        ScheduledSlideType.VIDEOS,
        ScheduledSlideType.ORDERSLIST,
        ScheduledSlideType.EVENTS,
        ScheduledSlideType.MENUS,
        ScheduledSlideType.PICS,
        ScheduledSlideType.ORDERSLIST,
            //ScheduledSlideType.KARAOKE,
        ];

    private constructor() {
        // use Manager.Instance to use this class
        this.clock = <HTMLDivElement>document.getElementById('clock');
        this.scheduleIndex = -1;

    }

    public static get Instance() {
        return this._instance || (this._instance = new this());
    }

    public getLocale(): string {
        return this.conf?.locale ?? 'en';
    }

    public getNumberOfEventsToShow(): number {
        return this.conf?.events_to_show ?? 6;
    }

    public areAnimationsEnabled(): boolean {
        return this.conf?.use_animations ?? false;
    }

    public isMarqueeEnabled(): boolean {
        return this.conf?.show_marquee ?? false;
    }

    public showCancelledEvents(): boolean {
        return this.conf?.show_cancelled_events ?? true;
    }

    public showEventsWhileHappening(): boolean {
        return this.conf?.show_event_while_is_happening ?? false;
    }

    public setClockPosition(id: number) {
        if (!this.clock) {
            return;
        }

        this.restoreClockPosition(); // case 5
        const PADDING = "1vh";
        // font size is 6vh;

        switch (id) {
            case 0:
                this.clock.style.visibility = 'hidden';
                break;
            case 1:
                this.clock.style.left = PADDING;
                this.clock.style.top = PADDING;
                break;
            case 2:
                this.clock.style.left = '50%';
                this.clock.style.transform = 'translate(-50%)';
                this.clock.style.top = PADDING;
                break;
            case 3:
                this.clock.style.right = PADDING;
                this.clock.style.top = PADDING;
                break;
            case 4:
                this.clock.style.top = '50%';
                this.clock.style.transform = 'translate(0, -50%)';
                this.clock.style.right = PADDING;
                break;
            case 6:
                this.clock.style.left = '50%';
                this.clock.style.transform = 'translate(-50%)';
                this.clock.style.bottom = PADDING;
                break;
            case 7:
                this.clock.style.left = PADDING;
                this.clock.style.bottom = PADDING;
                break;
            case 8:
                this.clock.style.left = PADDING;
                this.clock.style.bottom = "47vh";
                break;
            case 9:
                this.clock.style.left = "50%";
                this.clock.style.top = "50%";
                this.clock.style.transform = 'translate(-50%,-50%)';
                break;
        }
    }

    public restoreClockPosition() {
        if (!this.clock) {
            return;
        }
        this.clock.style.removeProperty('visibility');
        this.clock.style.removeProperty('transform');
        this.clock.style.removeProperty('bottom');
        this.clock.style.removeProperty('right');
        this.clock.style.removeProperty('top');
        this.clock.style.removeProperty('left');
    }

    init(monitorConfig: Config) {
        this.conf = monitorConfig;
        if (!monitorConfig.show_weather_forecast) {
            this.schedule = this.schedule.filter(slide => slide !== ScheduledSlideType.WEATHER);
        }

        if (!monitorConfig.show_menus) {
            this.schedule = this.schedule.filter(slide => slide !== ScheduledSlideType.MENUS);
        }

        if (!monitorConfig.show_pictures) {
            this.schedule = this.schedule.filter(slide => slide !== ScheduledSlideType.PICS);
        }

        if (!monitorConfig.show_videos) {
            this.schedule = this.schedule.filter(slide => slide !== ScheduledSlideType.VIDEOS);
        }

        if (!monitorConfig.show_karaoke) {
            this.schedule = this.schedule.filter(slide => slide !== ScheduledSlideType.KARAOKE);
        }

        if (!monitorConfig.show_orderslist) {
            this.schedule = this.schedule.filter(slide => slide !== ScheduledSlideType.ORDERSLIST);
        }
    }

    handleClick() {
        this.currentSlide?.next();
    }

    async moveToNextSchedule() {
        if (this.halt)
            return;
        await this.currentSlideSemaphore.acquire();
        if (this.currentSlide) {
            await this.stopSlide(this.currentSlide);
        }
        this.currentSlideSemaphore.release();
        this.nextSchedule();
        this.startScheduledSlide();
    }

    registerNothingAndCheck(slide: Slide) {
        this.slideHadNoData.set(slide, true);
        if (Array.from(this.slideHadNoData.values()).every(v => v)) {
            console.log(`All slides had no data, giving up`);
            this.stop();
        }
    }

    notify(source: Slide, event: SlideEvents) {
        console.log(`Notified ${event} by`, source);
        switch (event) {
            case SlideEvents.DONE:
            case SlideEvents.CYCLE_END:
                this.moveToNextSchedule();
                break;
            case SlideEvents.PAGE_END:
                // for now, just as info
                break;
            case SlideEvents.NOTHING:
                if (source.isInterruptable()) {
                    this.registerNothingAndCheck(source);
                }
                this.moveToNextSchedule();
                break;
            case SlideEvents.DATA_UPDATED:
                if (this.currentSlide === source) {
                    this.refreshSlide(source);
                }
                break;
            case SlideEvents.ERROR:
                this.moveToNextSchedule();
                break;
            default:
                console.error('Unknown Schedule Event', event);
                break;
        }
    }

    async refreshSlide(slide: Slide) {
        console.log(`[Manager] Refreshing slide`);
        await this.stopSlide(slide);
        this.startSlide(slide);
    }

    async refreshCurrentSlide() {
        if (this.currentSlide) {
            await this.refreshSlide(this.currentSlide);
        }
    }

    registerSlide(slide: Slide, type: SlideType) {
        this.slides.set(type, slide);
        if (slide.isInterruptable()) {
            this.slideHadNoData.set(slide, false);
        }
    }

    start() {
        this.moveToNextSchedule();
    }

    nextSchedule() {
        //const oldIndex = this.scheduleIndex;
        this.scheduleIndex = (this.scheduleIndex + 1) % this.schedule.length;
        console.debug(`Schedule index increased to ${this.scheduleIndex}`);
        //if (oldIndex > this.scheduleIndex) {
        //console.debug('[Schedule] One iteration completed, checking if data is up to date');
        //pullTimeDeltaFromServer(); // sometimes the clocks of the raspberry pi goes out of sync
        // checkDataIsUpToDate();
        //}
    }

    private delay(ms: number) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async waitForAnimationToFinish(slide: Slide) {
        while (slide.isAnimationRunning()) {
            await this.delay(300);
        }
    }

    startScheduledSlide(attempt = 0) {
        const slide = this.slides.get(this.getCurrentScheduleType());
        if (!slide) {
            console.warn(`[Manager] Slide for type "${this.getCurrentScheduleType()}" not found`);
            if (attempt < 10) {
                this.nextSchedule();
                this.startScheduledSlide(attempt + 1);
                return;
            } else {
                console.error('Critical error, scheduled slide not found. Aborting.');
                return;
            }
        }

        console.log(`[Manager] Starting next scheduled slide "${this.getCurrentScheduleType()}"`);
        console.dir(slide);
        this.startSlide(slide);
    }

    /**
     * Skip the schedule execution to the given type
     * @param type 
     */
    skipScheduleTo(type: ScheduledSlideType) {
        this.scheduleIndex = this.schedule.indexOf(type);
        if (this.scheduleIndex < 0) {
            console.error('[Manager] requested schedule not found');
            this.scheduleIndex = 0;
        }
        this.startScheduledSlide();
    }

    getCurrentScheduleType() {
        return this.schedule[this.scheduleIndex];
    }

    isShowing(type: SlideType) {
        return this.getCurrentScheduleType() === type;
    }

    async testFunction() {
        for (let i = 0; i < 10; i++) {
            console.log('testFunction', i);
            await this.delay(1000);
        }
    }

    async startSlide(slide: Slide, forceRefresh = false) {
        console.log("[Manager] starting slide", slide);
        await this.currentSlideSemaphore.acquire();


        console.debug("[Manager] Semaphore acquired");
        if (this.currentSlide && this.currentSlide !== slide) {
            if (slide.isInterruptable() && !this.currentSlide.isInterruptable()) {
                console.dir(this.currentSlide);
                this.currentSlideSemaphore.release();
                throw new Error("Interrupting a not interruptable slide");
                // todo: check priority
            }
            console.log("Another slide was running, stopping it");
            await this.stopSlide(this.currentSlide)
        } else if (this.currentSlide === slide /* && forceRefresh*/) {
            console.log("The current slide must be refreshed, pausing it.");
            this.pauseSlide(slide)
        }

        if (slide.getState() === SlideState.NONE) {
            slide.onCreate();
        }
        else if (slide.getState() === SlideState.STOPPED) {
            slide.onRestart();
        }

        if ([SlideState.CREATED, SlideState.RESTARTED].includes(slide.getState())) {
            slide.onStart();
        }

        if ([SlideState.STARTED, SlideState.PAUSED].includes(slide.getState())) {
            slide.onResume();
        }

        this.currentSlide = slide;
        console.debug("[Manager] Semaphore released");
        this.currentSlideSemaphore.release();
    }

    pauseSlide(slide: Slide) {
        if (slide.getState() === SlideState.RUNNING) {
            slide.onPause();
        }
    }

    stop() {
        this.halt = true;
    }

    async stopSlide(slide: Slide) {
        console.log("[Manager] stopping slide", slide);
        if (slide.getState() === SlideState.RUNNING) {
            slide.onPause();
            slide.onStop();
        } else {
            console.error("[Manager] stopping the given slide, but it was not running!", slide)
        }

        if (this.currentSlide === slide) {
            await this.waitForAnimationToFinish(this.currentSlide);
            this.currentSlide = null;
        } else {
            console.error("[Manager] stopping the given slide, but it was not current!", slide, this.currentSlide)
        }
    }

    destroySlide(slide: Slide) {
        if (slide.getState() === SlideState.RUNNING) {
            slide.onPause();
            slide.onStop();
            slide.onDestroy();
        }
        if (this.currentSlide === slide)
            this.currentSlide = null;
    }

    restoreMainColor() {
        document.documentElement.style.removeProperty('--main-color');
        document.documentElement.style.removeProperty('--background-color');
        //this.clock.style.removeProperty('color');
        this.resetClockColor();
    }

    setColors(foreground: string, background: string) {
        this.setMainColor(foreground);
        this.setBackgroundColor(background);
    }

    setMainColor(color: string) {
        document.documentElement.style.setProperty('--main-color', color);
    }

    setBackgroundColor(color: string) {
        document.documentElement.style.setProperty('--background-color', color);
    }

    setClockColor(color: string) {
        this.clock.style.setProperty('color', color);
    }

    resetClockColor() {
        this.clock.style.removeProperty('color');
    }

    setClockBackgroundColor(color: string) {
        this.clock.style.setProperty('background-color', color);
    }

    resetClockBackgroundColor() {
        this.clock.style.setProperty('background-color', 'initial');
    }
}