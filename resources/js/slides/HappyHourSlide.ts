import { SlideEvents } from "../manager.js";
import { HappyHour } from "../types.js";
import { Countdown } from "../utilities/Countdown.js";
import dayjs from 'dayjs/esm/index.js';
import { UnskippableSlide } from "./UnskippableSlide.js";

export class HappyHourSlide extends UnskippableSlide {

    private countdown: Countdown | null = null;
    private deadline: dayjs.Dayjs | null = null;

    private initSlide(deadline: dayjs.Dayjs) {
        this.deadline = deadline;
        this.countdown = new Countdown(this.deadline.format('YYYY-MM-DDTHH:mm:ss'), this.div.querySelector('.seconds') as HTMLSpanElement, this.div.querySelector('.minutes') as HTMLSpanElement, this.div.querySelector('.hours') as HTMLSpanElement, null);
    }

    initHappyHour(hh: HappyHour | null) {
        if (!hh) {
            this.notifyMediator(SlideEvents.NOTHING);
            return;
        }
        this.initSlide(dayjs(hh.end));
        (this.div.querySelector('#price') as HTMLSpanElement).innerText = hh.price;
        (this.div.querySelector('#happy-hour-drink') as HTMLDivElement).innerText = hh.drink;
        (this.div.querySelector('#happy-hour-extra') as HTMLDivElement).innerText = hh.info;
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