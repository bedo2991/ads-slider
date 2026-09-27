import { Mediator } from "../patterns/Mediator.js";
import { MiniSlide } from "./MiniSlides.js";
import { UnskippableSlide } from "./UnskippableSlide.js";

export class MarketingAfter extends UnskippableSlide {
    private interval: ReturnType<typeof setInterval> | null = null;
    private sentenceHolder: HTMLDivElement | null = null;
    private sentences: string[] = [];

    constructor(mediator: Mediator, mainDiv: HTMLDivElement, sentences: string[] = []) {
        super(mediator, mainDiv);
        this.sentences = sentences;
    }

    setSentences(sentences: string[]): void {
        this.sentences = sentences;
    }

    getSentences(): string[] {
        return this.sentences;
    }

    onCreate(): void {
        super.onCreate();
        console.log("MarketingAfter created");
        this.sentenceHolder = document.getElementById('mitgliederWerbung') as HTMLDivElement;
    }

    onStart(): void {
        super.onStart();
        this.displaySlide();
        MiniSlide.Instance.show();
        console.log("MarketingAfter started");
    }

    onResume(): void {
        super.onResume();
        this.startInterval();
        console.log("MarketingAfter resumed");
        console.debug("[30Mins] Showing 'thirty minutes after'");
    }

    onPause(): void {
        super.onPause();
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
        console.log("MarketingAfter paused");
    }

    onStop(): void {
        super.onStop();
        this.hideSlide();
        MiniSlide.Instance.hide();
        console.log("MarketingAfter stopped");
    }

    onRestart(): void {
        super.onRestart();
        this.displaySlide();
        console.log("MarketingAfter restarted");
    }

    onDestroy(): void {
        super.onDestroy();
        this.sentenceHolder = null;
        this.div.remove();
        console.log("MarketingAfter destroyed");
    }

    private startInterval() {
        if (this.interval === null) {
            this.interval = setInterval(() => { this.nextSlide() }, 25000);
            this.nextSlide();
        }
    }

    nextSlide(): void {
        console.log("Next sentence", this.sentenceHolder);
        if (this.sentenceHolder)
            this.sentenceHolder.innerText = this.getSentence();
    }

    next(): void {
        this.nextSlide();
        MiniSlide.Instance.next();
    }

    getSentence(): string {
        if (!this.sentences || this.sentences.length === 0) {
            return '';
        }
        return this.sentences[Math.floor(Math.random() * this.sentences.length)];
    }
}