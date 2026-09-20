import { Manager } from "../manager.js";
import { MiniSlide } from "./MiniSlides.js";
import { UnskippableSlide } from "./UnskippableSlide.js";

export class ClosingSlide extends UnskippableSlide {
    onStart(): void {
        super.onStart();
        this.displaySlide();
    }

    onResume(): void {
        super.onResume();
        MiniSlide.Instance.show();
    }

    onStop(): void {
        super.onStop();
        MiniSlide.Instance.hide();
        Manager.Instance.restoreMainColor();
        this.hideSlide();
    }

    next(): void {
        MiniSlide.Instance.next();
    }
}