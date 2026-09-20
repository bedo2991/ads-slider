import dayjs from 'dayjs/esm/index.js'
import isBetween from 'dayjs/esm/plugin/isBetween/index.js'
dayjs.extend(isBetween);

import { Video, VideoSlide } from "../types.js";
import { Mediator } from "../patterns/Mediator.js";
import { Manager, SlideEvents } from '../manager.js';
import { Slide, SlideState } from './Slide.js';

export class VidsSlide extends Slide {
    private currentIndex = -1;
    private videoSlides: VideoSlide[] = [];
    private videoSrcElement: HTMLSourceElement | null = null;
    private videoElement: HTMLVideoElement | null = null;
    private clock: HTMLDivElement | null = null;
    private base_root: string;
    private api_token: string;

    constructor(mediator: Mediator, mainDiv: HTMLDivElement, base_root: string, api_token: string) {
        super(mediator, mainDiv);
        this.base_root = base_root;
        this.api_token = api_token;
    }

    setVideos(videos: VideoSlide[]) {
        console.log("[Vids] Set videos");
        this.videoSlides = videos;
        console.log(`[Vids] Got ${this.videoSlides.length} slides`);
        const now = dayjs();
        this.videoSlides = this.videoSlides.filter((slide) => now.isBetween(slide.startDate, slide.endDate));
        console.log(`[Vids] After filtering: ${this.videoSlides.length} slides`);
        this.notifyMediator(SlideEvents.DATA_UPDATED);
    }

    onStart(): void {
        this.videoElement = <HTMLVideoElement>document.getElementById('mainVideo');
        this.videoSrcElement = <HTMLSourceElement>document.getElementById('mainVideoSource');
        this.clock = <HTMLDivElement>document.getElementById('clock');
        this.currentIndex = -1;
        if (this.videoSlides.length === 0) {
            console.warn("No Vids to display");
            this.notifyMediator(SlideEvents.NOTHING);
            return;
        }
        super.onStart(); // change the state now, after you are sure there are videos to show.
        this.addEventListeners();
        this.displaySlide();
    }

    onResume(): void {
        super.onResume();
        this.nextVideo();
        const readyState = this.videoElement?.readyState;
        if (readyState && readyState >= 3) {
            this.videoElement?.play();
        }
    }

    onPause(): void {
        super.onPause();
        this.videoElement?.pause();
    }

    onStop(): void {
        super.onStop();

        Manager.Instance.restoreClockPosition();
        Manager.Instance.resetClockColor();

        this.hideSlide();

        this.videoSrcElement?.removeAttribute('src');
        this.videoElement?.load();

        this.videoElement = null;
        this.videoSrcElement = null;
        this.clock = null;

        console.log("Vids stopped");
    }

    onDestroy(): void {
        super.onDestroy();
        this.div.remove();
        console.log("Vids destroyed");
    }

    private checkVideosAreUpToDate(): boolean {
        // Check if the pictures are still up to date
        const now = dayjs();
        let unchanged = true;
        for (let i = this.videoSlides.length - 1; i >= 0; i--) {
            if (!now.isBetween(this.videoSlides[i].startDate, this.videoSlides[i].endDate)) {
                this.videoSlides.splice(i, 1);
                unchanged = false;
            }
        }
        return unchanged;
    }

    private setColors(background: string, clock: string) {
        this.div.style.backgroundColor = background;

        if (this.clock) {
            this.clock.style.color = clock;
            this.clock.style.backgroundColor = 'transparent';
        }
    }

    private showvideo() {
        console.log("[Vids] Showing video");

        const video: Video = this.videoSlides[this.currentIndex].scheduleable;
        this.setColors(video.bg_color, video.color);
        Manager.Instance.setClockPosition(this.videoSlides[this.currentIndex].scheduleable.clock_location);


        const request = indexedDB.open('slider', 2); // Remember to increase the database version in videosWorker.ts too, if changed!
        request.onsuccess = (/*event*/) => {
            const db: IDBDatabase = request.result;
            const videosTransaction = db.transaction('videos', 'readonly').objectStore('videos');
            const readVideo = videosTransaction.get(video.id);
            readVideo.onsuccess = () => {
                if (readVideo.result !== undefined) {
                    console.dir(readVideo.result.video);
                    if (readVideo.result.video) {
                        if (this.videoSrcElement) {
                            this.videoSrcElement.src = URL.createObjectURL(readVideo.result.video);
                            this.videoSrcElement.type = 'video/mp4';
                        } else {
                            this.notifyMediator(SlideEvents.ERROR);
                        }
                        // start loading the video
                        this.videoElement?.load();
                    } else {
                        this.notifyMediator(SlideEvents.ERROR);
                    }
                } else {
                    this.notifyMediator(SlideEvents.ERROR);
                }
            };
        };
        request.onerror = (/*event*/) => {
            this.notifyMediator(SlideEvents.ERROR);
        }
    }

    private nextVideo() {
        console.log("[Vids] Next video");
        this.currentIndex = this.currentIndex + 1;
        if (this.currentIndex >= this.videoSlides.length) {
            console.log("[Vids] end of list reached");
            this.notifyMediator(SlideEvents.CYCLE_END);
            return;
        }

        if (!this.checkVideosAreUpToDate()) {
            // at least one video has expired
            if (this.videoSlides.length === 0) {
                // all videos have expired
                this.notifyMediator(SlideEvents.NOTHING);
                return;
            }
            // there is at least one video...
            if (this.currentIndex >= this.videoSlides.length) {
                // but our index is currently out of bound, reset it to 0
                this.currentIndex = 0;
            }
        }

        this.showvideo();
    }

    private addEventListeners() {
        this.videoElement?.addEventListener('ended', () => {
            console.debug('[Video] ended');
            this.nextVideo();
        });

        this.videoElement?.addEventListener('canplaythrough', (ev) => {
            // If the browser thinks the video can be played without buffering, it starts
            console.debug('[Video] canplaythrough');
            const playPromise = (ev.target as HTMLVideoElement).play();
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    // Automatic playback started! Let it run
                }).catch((error) => {
                    console.error('Could not play video, nextSlide', error);
                    this.notifyMediator(SlideEvents.ERROR);
                });
            }
        });
    }

    next(): void {
        if (this.getState() === SlideState.RUNNING) {
            this.nextVideo();
        }
    }
}