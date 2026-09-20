import { Manager } from "../manager.js";
import { AdsEvent } from "../types.js";
import { addAnimationOnce } from "../utilities/animations.js";
import { fillInComponentSafeStripeNewlines, hideElement, showElement } from "../utilities/misc.js";
import { getDisplayDate } from "../utilities/time.js";
import { EventsSlide } from "./EventsSlide.js";

export class MiniSlide {
    private events: AdsEvent[] = [];
    private static _instance: MiniSlide;

    private slidesSpeedMs = 20000;

    private currentSlideIndex = -1;
    private slideInterval: ReturnType<typeof setInterval> | null = null;

    private constructor() {
        // Singleton
    }

    public static get Instance() {
        return this._instance || (this._instance = new this());
    }

    setEvents(events: AdsEvent[]) {
        this.events = EventsSlide.filterEvents(events);
        this.events = this.events.slice(0, Manager.Instance.getNumberOfEventsToShow());
        if (this.events.length === 0) {
            this.hide();
            return;
        }
        this.currentSlideIndex = 0;
    }

    show() {
        if (this.events.length === 0) {
            console.warn("No events to show in MiniSlide.");
            return;
        }
        showElement(document.getElementById('event-slide-small') as HTMLDivElement);
        if (!this.slideInterval) {
            this.slideInterval = setInterval(() => {
                this.next();
            }, this.slidesSpeedMs);
            this.next();
        }
    }

    hide() {
        if (this.slideInterval) {
            clearInterval(this.slideInterval);
            this.slideInterval = null;
        }
        hideElement(document.getElementById('event-slide-small') as HTMLDivElement);
    }

    next(): void {
        this.currentSlideIndex = (this.currentSlideIndex + 1) % this.events.length;
        this.showSlide();
    }

    private showSlide(): void {
        const e = this.events[this.currentSlideIndex];
        if (!e) {
            return;
        }
        if (e.color) {
            Manager.Instance.setMainColor(e.color);
        } else {
            Manager.Instance.restoreMainColor();
        }
        const icon = document.getElementById('main-icon-small') as HTMLSpanElement;
        icon.setAttribute('class', '');
        icon.classList.add('fas', `fa-${e.icon}`);
        addAnimationOnce(icon, 'fadeInLeft');

        const startDate = document.getElementById('start-small') as HTMLSpanElement;
        startDate.innerText = getDisplayDate(e.startDate, Manager.Instance.getLocale());

        const startTime = document.getElementById('start-time-small') as HTMLSpanElement;
        startTime.innerText = e.startDate.format('LT') + (Manager.Instance.getLocale() === 'de' ? ' Uhr' : '');

        addAnimationOnce(document.getElementById('event-details-small') as HTMLDivElement, 'fadeInRight');

        const name = document.getElementById('event-name-small') as HTMLHeadingElement;
        fillInComponentSafeStripeNewlines(name, e.name);
        name.classList.toggle('cancelled', !!e.cancelled);
        addAnimationOnce(name, 'flash');
    }
}