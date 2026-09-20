import { Manager, SlideEvents } from "../manager.js";
import { Slide } from "./Slide.js";
import dayjs from 'dayjs/esm/index.js'

export class WeatherForecastSlide extends Slide {

    private showSlideFor = 20000;

    private weatherDataLastUpdate: number | null = null;

    private slideTimeout: ReturnType<typeof setTimeout> | null = null;

    onStart(): void {
        super.onStart();
        Manager.Instance.restoreMainColor();
        this.displaySlideWithAnimation('bounceInLeft');
        console.log("WeatherForecast started");
    }

    onResume(): void {
        super.onResume();
        if (!this.slideTimeout) {
            this.slideTimeout = setTimeout(() => {
                this.notifyMediator(SlideEvents.DONE);
            }, this.showSlideFor);
        }
    }

    onStop(): void {
        super.onStop();
        this.clearTimeout();
        //this.hideSlide();
        this.hideSlideWithAnimation('bounceOutLeft');
    }

    onDestroy(): void {
        super.onDestroy();
        this.div.remove();
        console.log("WeatherForecast destroyed");
    }

    next(): void {
        this.clearTimeout();
        this.notifyMediator(SlideEvents.DONE);
    }

    private clearTimeout() {
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
    }
}