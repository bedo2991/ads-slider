import { SlideEvents } from "../manager.js";
import { Countdown } from "../utilities/Countdown.js";
import dayjs from 'dayjs/esm/index.js';
import { UnskippableSlide } from "./UnskippableSlide.js";

export class LastCallSlide extends UnskippableSlide {
    private countdown: Countdown | null = null;
    private deadline: dayjs.Dayjs | null = null;

    initSlide(deadline: dayjs.Dayjs) {
        this.deadline = deadline;
        this.countdown = new Countdown(this.deadline.format('YYYY-MM-DDTHH:mm:ss'), document.querySelector('#final-round-container .seconds') as HTMLSpanElement, document.querySelector('#final-round-container .minutes') as HTMLSpanElement, null, null);
    }

    onResume(): void {
        super.onResume();
        this.displaySlide();

        if (!this.deadline || !this.countdown) {
            throw "Initialize the deadline, first";
        }

        Countdown.setCallback(() => {
            this.notifyMediator(SlideEvents.DONE);
        });
        this.countdown?.start();
    }


    onStop(): void {
        super.onStop();
        this.hideSlideWithAnimation('bounceOutUp');
        this.countdown?.stop(true);
    }
}