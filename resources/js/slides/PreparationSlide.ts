import dayjs from 'dayjs/esm/index.js'
import { autosizeText, fillInComponentSafe } from "../utilities/misc.js";
import { Manager, SlideEvents } from "../manager.js";
import { Countdown } from "../utilities/Countdown.js";
import { UnskippableSlide } from './UnskippableSlide.js';

export class PreparationSlide extends UnskippableSlide {
    private deadline: dayjs.Dayjs | null = null;
    private countdown: Countdown | null = null;

    setDeadline(deadline: dayjs.Dayjs) {
        console.log("Set deadline to ", deadline);
        this.deadline = deadline;
        this.countdown = new Countdown(this.deadline.format('YYYY-MM-DDTHH:mm:ss'), document.querySelector('#preparations .seconds') as HTMLSpanElement, document.querySelector('#preparations .minutes') as HTMLSpanElement, null, null);
    }

    setIcon(icon: string) {
        console.log("Setting icon to " + icon);
        const iconEl = document.getElementById('preparations-icon');
        if (iconEl) {
            iconEl.setAttribute('class', '');
            iconEl.classList.add('fas', `fa-${icon}`);
            if (Manager.Instance.areAnimationsEnabled()) {
                iconEl.classList.add('animate__animated', 'animate__pulse', 'animate__infinite');
            }
        }
    }

    setTitle(title: string) {
        console.log("Setting icon to " + title);
        const el = document.querySelector('#preparations .event-name') as HTMLDivElement;
        if (el) {
            fillInComponentSafe(el, title);
        }
    }

    initSlide(title: string, icon: string) {
        console.log("Slide init");
        this.setTitle(title);
        this.setIcon(icon);
    }

    onResume(): void {
        super.onResume();
        this.displaySlide();

        // this has to be done after the slide is visible, otherwise it does not work
        autosizeText(document.querySelector('#preparations .event-name') as HTMLDivElement);


        if (!this.deadline || !this.countdown) {
            throw "Initialize the deadline, first";
        }
        Countdown.setCallback(() => {
            this.notifyMediator(SlideEvents.DONE);
        });
        this.countdown.start();
    }

    onStop(): void {
        super.onStop();
        this.hideSlideWithAnimation('bounceOutUp');
        this.countdown?.stop(true);
    }
}