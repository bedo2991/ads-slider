import dayjs from 'dayjs/esm/index.js'
import { SlideLifecycle } from "../SlideLifecycle.js";
import { Component } from '../patterns/Component.js';
import { Mediator } from '../patterns/Mediator.js';
import { showElement } from '../utilities/misc.js';
import { Manager } from '../manager.js';

export enum SlideState {
    NONE,
    CREATED,
    RESTARTED,
    STARTED,
    RUNNING,
    PAUSED,
    STOPPED,
    DESTROYED
}

export abstract class Slide extends Component implements SlideLifecycle {
    private state: SlideState = SlideState.NONE;
    private animating = false;

    protected div: HTMLDivElement;
    protected plannedStop: dayjs.Dayjs | null = null;

    constructor(mediator: Mediator, mainDiv: HTMLDivElement) {
        super(mediator);
        this.div = mainDiv;
        this.div.dataset.opening = "false";
        this.div.dataset.closing = "false";
    }

    getState(): SlideState {
        return this.state;
    }

    isInterruptable(): boolean {
        return true;
    }

    /**
     * Probably not needed
     * @deprecated
     * @param dateTime
     */
    setPlannedStop(dateTime: dayjs.Dayjs) {
        this.plannedStop = dateTime;
    }

    displaySlide() {
        showElement(this.div);
    }

    displaySlideWithAnimation(animationName: string, callback?: () => void) {
        this.div.style.display = "block";

        if (!Manager.Instance.areAnimationsEnabled()) {
            if (callback) callback();
            return;
        }

        this.div.addEventListener('animationend', (event) => {
            event.stopPropagation();
            this.div.classList.remove('animate__animated', `animate__${animationName}`);
            this.animating = false;
            if (callback) callback();
        }, { once: true });

        this.div.setAttribute("class", "")
        this.div.classList.add('animate__animated', `animate__${animationName}`);
        this.animating = true;
    }

    hideSlide() {
        this.div.style.display = "none";
    }

    hideSlideWithAnimation(animationName: string, callback?: () => void) {
        if (!Manager.Instance.areAnimationsEnabled() || this.div.style.display === "none") {
            this.div.style.display = "none";
            if (callback) callback();
            return;
        }

        this.div.addEventListener('animationend', (event) => {
            event.stopPropagation();
            if (this.state === SlideState.STOPPED || this.state === SlideState.DESTROYED) {
                this.div.classList.remove('animate__animated', `animate__${animationName}`);
                this.div.style.display = "none";
            } else {
                this.div.classList.remove('animate__animated');
            }
            this.animating = false;
            if (callback) callback();
        }, { once: true });
        this.div.classList.add('animate__animated', `animate__${animationName}`);
        this.animating = true;
    }

    isAnimationRunning() {
        return this.animating;
    }

    abstract next(): void;

    onCreate(): void {
        console.log(`Slide ${this.div.id} created`);
        if (this.state !== SlideState.NONE) throw new Error("Invalid state transition");
        this.state = SlideState.CREATED;
    }

    onStart(): void {
        console.log(`Slide ${this.div.id} started`);
        if (this.state !== SlideState.CREATED && this.state !== SlideState.RESTARTED) throw new Error("Invalid state transition");
        this.state = SlideState.STARTED;
    }

    onResume(): void {
        console.log(`Slide ${this.div.id} resumed`);
        if (this.state !== SlideState.STARTED && this.state !== SlideState.PAUSED) throw new Error("Invalid state transition");
        this.state = SlideState.RUNNING;
    }

    onPause(): void {
        console.log(`Slide ${this.div.id} paused`);
        if (this.state !== SlideState.RUNNING) throw new Error("Invalid state transition");
        this.state = SlideState.PAUSED;
    }

    onStop(): void {
        console.log(`Slide ${this.div.id} stopped`);
        if (this.state !== SlideState.PAUSED) throw new Error("Invalid state transition");
        this.state = SlideState.STOPPED;
    }

    onRestart(): void {
        console.log(`Slide ${this.div.id} restarted`);
        if (this.state !== SlideState.STOPPED) throw new Error("Invalid state transition");
        this.state = SlideState.RESTARTED;
    }

    onDestroy(): void {
        console.log(`Slide ${this.div.id} destroyed`);
        if (this.state !== SlideState.STOPPED) throw new Error("Invalid state transition");
        this.state = SlideState.DESTROYED;
    }
}