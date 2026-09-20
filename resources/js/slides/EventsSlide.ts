import dayjs from 'dayjs/esm/index.js'
import { Manager, SlideEvents } from "../manager.js";
import { AdsEvent } from "../types.js";
import { addAnimationOnce } from "../utilities/animations.js";
import { fillInComponentSafe, hideElement, showElement } from "../utilities/misc.js";
import { getDisplayDate } from "../utilities/time.js";
import { Slide } from "./Slide.js";
import QR from 'qrcode';

export class EventsSlide extends Slide {
    private slidesSpeedMs = 20000;

    private currentSlideIndex = -1;

    private progressBar: HTMLDivElement | null = null;
    private qrCanvas: HTMLCanvasElement | null = null;

    private events: AdsEvent[] = [];
    private filteredEvents: AdsEvent[] = [];
    private marqueeText: string | null = null;

    private slideInterval: ReturnType<typeof setInterval> | null = null;

    private initSlideDuration() {
        if (this.filteredEvents.length < 4)
            this.slidesSpeedMs = 40000;
        else if (this.filteredEvents.length < 8)
            this.slidesSpeedMs = 20000;
        // if(ads.length >= 8)
        else
            this.slidesSpeedMs = 13000;
    }

    public static filterEvents(events: AdsEvent[]): AdsEvent[] {
        const now = dayjs();
        return events.filter(event => {
            if (event.disabled === true) {
                return false;
            }
            if (!Manager.Instance.showCancelledEvents() && event.cancelled === true) {
                return false;
            }
            if (Manager.Instance.showEventsWhileHappening()) {
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
        );
    }

    setMarqueeText(text: string) {
        if (Manager.Instance.isMarqueeEnabled()) {
            this.marqueeText = text;
            this.notifyMediator(SlideEvents.DATA_UPDATED);
        }
    }

    private filterAndCapEvents() {
        this.events = EventsSlide.filterEvents(this.events);
        this.filteredEvents = this.events.slice(0, Manager.Instance.getNumberOfEventsToShow());
    }

    setEvents(events: AdsEvent[]) {
        console.log("[Events] Set events");
        this.events = events;
        console.log(`[Events] Got ${this.events.length} events`);

        this.filterAndCapEvents();
        console.log(`[Events] Filtered to ${this.filteredEvents.length} events`);

        this.initSlideDuration();
        this.notifyMediator(SlideEvents.DATA_UPDATED);
    }

    onStart(): void {
        this.filterAndCapEvents();

        if (this.filteredEvents.length === 0) {
            console.log("[Events] No events to show");
            this.notifyMediator(SlideEvents.NOTHING);
            return;
        }

        super.onStart(); // change the state of the slide now, when you are sure there are events to show.

        this.displaySlide();
        this.progressBar = <HTMLDivElement>document.getElementById('progress-bar');
        this.qrCanvas = <HTMLCanvasElement>document.getElementById('qr-code');
        this.initProgressBar(this.filteredEvents.length);
        this.currentSlideIndex = -1;
        console.log("Events started");
    }

    onResume(): void {
        super.onResume();
        if (!this.slideInterval) {
            this.slideInterval = setInterval(() => {
                this.nextSlide();
            }, this.slidesSpeedMs);
        }
        this.nextSlide();
        if (this.marqueeText) {
            this.displayMarquee();
        }
        console.log("Events resumed");
    }

    onStop(): void {
        super.onStop();
        this.clearInterval();
        this.removeMarquee();
        this.hideSlide();
        //this.hideSlideWithAnimation('zoomOutRight');
        this.progressBar = null;
        this.qrCanvas = null;

        Manager.Instance.resetClockBackgroundColor();
        Manager.Instance.restoreMainColor();
        Manager.Instance.resetClockBackgroundColor();
        console.log("Events stopped");
    }

    onDestroy(): void {
        super.onDestroy();
        this.div.remove();
        console.log("Events destroyed");
    }

    private showSlide(): void {
        if (Manager.Instance.areAnimationsEnabled() && this.currentSlideIndex > 0) {
            addAnimationOnce(<HTMLDivElement>document.getElementById('left-part-container'), 'fadeOutUp');
            addAnimationOnce(<HTMLDivElement>document.querySelector('#event-slide .event-details'), 'fadeOutDown', () => {
                this.fillInEvent();
            });
        } else {
            this.fillInEvent();
        }
    }

    private fillInEvent(): void {
        this.highlightProgressBarElement();

        const e = this.filteredEvents[this.currentSlideIndex];

        if (e.color) {
            Manager.Instance.setMainColor(e.color);
        } else {
            Manager.Instance.restoreMainColor();
        }

        if (Manager.Instance.areAnimationsEnabled()) {
            if (this.currentSlideIndex === 0) {
                document.getElementById('progress')?.classList.remove('shrink');
                document.getElementById('progress')?.offsetHeight; // Trigger a reflow, flushing the CSS changes, otherwise no animation
                document.getElementById('progress')?.classList.add('shrink');
            } else {
                document.getElementById('progress')?.classList.toggle('shrink');
            }
        }

        if (e.link) {
            const options: QR.QRCodeRenderersOptions = {
                errorCorrectionLevel: 'L',
                margin: 1.5,
                color: {
                    dark: '#000000',
                    light: '#ffffff', // e.color,
                },
            };
            QR.toCanvas(
                this.qrCanvas,
                e.link,
                options,
                (error) => {
                    if (error)
                        console.error(error);

                    if (this.qrCanvas) {
                        this.qrCanvas.style.display = 'block';
                        console.log('QR code generated with success!');
                    }
                },
            );
        } else {
            if (this.qrCanvas) {
                this.qrCanvas.style.display = 'none';
            }
        }

        const icon = document.getElementById('main-icon') as HTMLSpanElement;
        icon.setAttribute("class", "");
        icon.classList.add('fas', `fa-${e.icon}`);

        const leftPartContainer = document.getElementById('left-part-container') as HTMLDivElement;
        leftPartContainer.setAttribute("class", "");
        addAnimationOnce(leftPartContainer, 'fadeInLeft');

        const startDate = document.getElementById('start') as HTMLDivElement;
        startDate.innerText = getDisplayDate(e.startDate, Manager.Instance.getLocale());

        const startTime = document.getElementById('start-time') as HTMLDivElement;
        startTime.innerText = e.startDate.format('LT') + (Manager.Instance.getLocale() === 'de' ? ' Uhr' : '');

        const placeDiv = document.querySelector('#event-slide .section') as HTMLDivElement;
        if (e.place) {
            fillInComponentSafe(document.getElementById('section') as HTMLSpanElement, e.place);
            showElement(placeDiv)
        } else {
            hideElement(placeDiv);
        }
        addAnimationOnce(<HTMLDivElement>document.querySelector('#event-slide .event-details'), 'fadeInRight');

        const eventName = document.querySelector('#event-slide .event-name') as HTMLHeadingElement;
        fillInComponentSafe(eventName, e.name);
        eventName.classList.toggle('cancelled', !!e.cancelled);
        addAnimationOnce(<HTMLDivElement>document.querySelector('#event-slide .event-details'), 'fadeInRight');
        addAnimationOnce(eventName, 'flash');
    }

    private nextSlide(): void {
        console.log("[Events] Next slide");
        this.currentSlideIndex = this.currentSlideIndex + 1;
        if (this.currentSlideIndex >= this.filteredEvents.length) {
            console.log("[Events] end of list reached");
            this.notifyMediator(SlideEvents.CYCLE_END);
            return;
        }
        this.showSlide();
    }

    next(): void {
        this.clearInterval();
        this.onPause();
        this.onResume();
    }

    private displayMarquee(): void {
        if (!this.marqueeText) return;

        console.debug('[Marquee] Setting Marquee');
        document.documentElement.style.setProperty(
            '--negative-marquee-length',
            `-${this.marqueeText.length}em`,
        );
        document.documentElement.style.setProperty(
            '--marquee-speed',
            `${(this.marqueeText.length * 0.1625) + 7}s`,
        );
        (document.getElementById('marqueeText') as HTMLParagraphElement).innerText = this.marqueeText;
        Manager.Instance.setClockBackgroundColor('var(--background-color)');
        showElement(document.getElementById('bar') as HTMLDivElement);
        console.debug('[Marquee] Added Marquee');
    }

    private removeMarquee() {
        hideElement(document.getElementById('bar') as HTMLDivElement);
        Manager.Instance.resetClockBackgroundColor();
        console.debug('[Marquee] Removed Marquee');
    }

    private clearInterval(): void {
        if (this.slideInterval) {
            clearInterval(this.slideInterval);
            this.slideInterval = null;
        }
    }

    private highlightProgressBarElement(): void {
        const index = this.currentSlideIndex;
        const el = this.progressBar?.children;
        if (!el) return;

        for (let i = 0; i < el.length; i += 1) {
            if (i === index) {
                el[i].classList.add('active');
            } else {
                el[i].classList.remove('active');
            }
        }
    }

    private initProgressBar(elNumber: number): void {
        if (!this.progressBar) {
            throw new Error("Progress bar not found");
        }
        this.progressBar.innerHTML = ""
        for (let i = 0; i < elNumber; i += 1) {
            const pbEl = document.createElement('div');
            pbEl.id = `pb_${i}`;
            pbEl.classList.add('pb_element');
            pbEl.innerHTML = `${i + 1 < 10 ? '&nbsp;' : ''}${i + 1}`;
            this.progressBar.appendChild(pbEl);
        }

        // If animations are enabled and the progress bar line was not created yet
        if (Manager.Instance.areAnimationsEnabled() && !document.getElementById('progress')) {
            // create the progress bar line
            const bar = document.createElement('div');
            bar.id = 'progress';
            this.progressBar.appendChild(bar);
            bar.style.transition = `width ${this.slidesSpeedMs}ms linear, background-color 2s linear`; // this second part should have the same value in slider.scss!
        }
        console.log("[Events] Progress bar initialized");
    }
}