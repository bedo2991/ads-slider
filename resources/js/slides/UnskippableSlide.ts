import { Slide } from "./Slide.js";

export abstract class UnskippableSlide extends Slide {

    private finished = false;

    next(): void {
        console.log("This slide cannot be skipped");
        return;
    }

    isInterruptable(): boolean {
        return false;
    }

    isFinished(): boolean {
        return this.finished;
    }
}