import dayjs from 'dayjs/esm/index.js'
import isBetween from 'dayjs/esm/plugin/isBetween/index.js'
dayjs.extend(isBetween);

import { PictureSlide } from "../types.js";
import { Mediator } from "../patterns/Mediator.js";
import { Manager, SlideEvents } from '../manager.js';
import { Slide, SlideState } from './Slide.js';

export class PicsSlide extends Slide {

    private showSlideFor = 15000;
    private currentIndex = -1;
    private pictureSlides: PictureSlide[] = [];
    private cachedPictures: HTMLImageElement[] = [];
    private picsContainer: HTMLDivElement | null = null;
    private clock: HTMLDivElement | null = null;
    private base_root: string;
    private api_token: string;
    private slideTimeout: ReturnType<typeof setTimeout> | null = null;

    constructor(mediator: Mediator, mainDiv: HTMLDivElement, base_root: string, api_token: string) {
        super(mediator, mainDiv);
        this.base_root = base_root;
        this.api_token = api_token;
    }

    setPictures(pictures: PictureSlide[]) {
        console.log("[Pics] Set pictures");
        this.pictureSlides = pictures;
        console.log(`[Pics] Got ${this.pictureSlides.length} slides`);
        const now = dayjs();
        this.pictureSlides = this.pictureSlides.filter((slide) => now.isBetween(slide.startDate, slide.endDate));
        console.log(`[Pics] After filtering: ${this.pictureSlides.length} slides`);
        this.pictureSlides.forEach((p) => this.cachePicture(p.scheduleable_id));
        this.notifyMediator(SlideEvents.DATA_UPDATED);
    }

    private cachePicture(id: number) {
        if (!this.cachedPictures[id]) {
            console.debug(`[Pics] caching picture with id ${id}`);
            this.cachedPictures[id] = new Image();
            this.cachedPictures[id].alt = '';
            if (Manager.Instance.areAnimationsEnabled()) {
                this.cachedPictures[id].className = 'animate__animated animate__fadeIn';
            }
            const width = window.innerWidth;
            const height = window.innerHeight;
            this.cachedPictures[
                id
            ].src = `${this.base_root}pics/${id}?api_token=${this.api_token}&width=${width}&height=${height}`;
            console.debug(`[Pics] cached picture with id ${id}`);
        } else {
            console.debug(`[Pics] pic with id ${id} was already cached`);
        }
    }

    private checkPicturesAreUpToDate(): boolean {
        // Check if the pictures are still up to date
        const now = dayjs();
        let unchanged = true;
        for (let i = this.pictureSlides.length - 1; i >= 0; i--) {
            if (!now.isBetween(this.pictureSlides[i].startDate, this.pictureSlides[i].endDate)) {
                this.removePictureByIndex(i);
                unchanged = false;
            }
        }
        return unchanged;
    }

    private removePictureByIndex(index: number): void {
        const pic_id = this.pictureSlides[index].scheduleable_id;
        this.pictureSlides.splice(index, 1);
        delete (this.cachedPictures[pic_id]);
    }

    onStart(): void {
        this.picsContainer = <HTMLDivElement>document.getElementById('pics-container');
        this.clock = <HTMLDivElement>document.getElementById('clock');
        this.currentIndex = -1;
        if (this.pictureSlides.length === 0) {
            this.notifyMediator(SlideEvents.NOTHING);
            console.warn("No pics to display");
            return;
        }
        super.onStart(); // change the state now, after you are sure there are pictures to show.
        this.displaySlide();
    }

    onResume(): void {
        super.onResume();
        this.nextPicture();
    }

    onPause(): void {
        super.onPause();
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
    }

    onStop(): void {
        super.onStop();
        Manager.Instance.restoreClockPosition();
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
        Manager.Instance.restoreMainColor();
        this.hideSlide();
        if (this.picsContainer) {
            this.picsContainer.innerHTML = "";
            this.picsContainer = null;
        }

        this.clock = null;
    }

    onDestroy(): void {
        super.onDestroy();
        this.div.remove();
    }

    private setColors(background: string, clock: string) {
        this.div.style.backgroundColor = background;

        if (this.clock) {
            this.clock.style.color = clock;
            this.clock.style.backgroundColor = 'transparent';
        }
    }

    private showPicture() {
        console.log("[Pics] Showing picture");
        if (!this.picsContainer) {
            throw new Error("PicsContainer not initialized");
        }
        const pic_id = this.pictureSlides[this.currentIndex].scheduleable_id;
        if (!this.cachedPictures[pic_id]) {
            console.error(`Cached pic with ID "${pic_id}" not found.`);
            this.notifyMediator(SlideEvents.ERROR);
        }
        this.picsContainer.innerHTML = "";
        this.picsContainer.appendChild(this.cachedPictures[pic_id]);
        this.setColors(this.pictureSlides[this.currentIndex].scheduleable.bg_color, this.pictureSlides[this.currentIndex].scheduleable.color)
        Manager.Instance.setClockPosition(this.pictureSlides[this.currentIndex].scheduleable.clock_location);
    }

    private nextPicture() {
        console.log("[Pics] Next picture");
        this.currentIndex = this.currentIndex + 1;
        if (this.currentIndex >= this.pictureSlides.length) {
            console.log("[Pics] end of list reached");
            this.notifyMediator(SlideEvents.CYCLE_END);
            return;
        }

        if (!this.checkPicturesAreUpToDate()) {
            // at least one picture has expired
            if (this.pictureSlides.length === 0) {
                // all pictures have expired
                this.notifyMediator(SlideEvents.NOTHING);
                return;
            }
            // there is at least one picture...
            if (this.currentIndex >= this.pictureSlides.length) {
                // but our index is currently out of bound, reset it to 0
                this.currentIndex = 0;
            }
        }

        this.showPicture();

        // Schedule next picture
        const currentPic = this.pictureSlides[this.currentIndex];
        const duration = (currentPic.scheduleable.duration > 0 ? currentPic.scheduleable.duration : 15) * 1000;

        if (this.slideTimeout) clearTimeout(this.slideTimeout);
        this.slideTimeout = setTimeout(() => {
            if (this.getState() === SlideState.RUNNING) {
                this.nextPicture();
            }
        }, duration);
    }

    next(): void {
        this.nextPicture();
    }
}