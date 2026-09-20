import dayjs from 'dayjs/esm/index.js'
import { addLeadingZero } from "./time.js";
import { hideElement, showElement } from "./misc.js";

export class Countdown {
    private static countdownIsRunning = false;
    private static countdownRuntimeIsRunning = false;
    private static previousTimeStamp: DOMHighResTimeStamp | null = null;
    private static timeRemaining = 1;
    private static endDateTime = 0;

    private static callback: (() => void) | null = null;

    private static days = 0;
    private static hours = 0;
    private static minutes = 0;
    private static seconds = 0;

    private static s: HTMLSpanElement;
    private static m: HTMLSpanElement | null = null;
    private static h: HTMLSpanElement | null = null;
    private static d: HTMLSpanElement | null = null;

    constructor(endDate: string, seconds: HTMLSpanElement, minutes: HTMLSpanElement | null = null, hours: HTMLSpanElement | null = null, days: HTMLSpanElement | null = null) {
        this.setEndDate(endDate);
        Countdown.s = seconds;
        Countdown.m = minutes;
        Countdown.h = hours;
        Countdown.d = days;

        Countdown.countdownIsRunning = false;
        Countdown.countdownRuntimeIsRunning = false;
        Countdown.previousTimeStamp = null;
    }

    static setCallback(cb: () => void) {
        Countdown.callback = cb;
    }

    setEndDate(endDate: string): void {
        Countdown.endDateTime = new Date(endDate).getTime();
    }

    static isRunning(): boolean {
        return Countdown.countdownIsRunning;
    }

    start(): void {
        if (Countdown.countdownRuntimeIsRunning) {
            return;
        }
        Countdown.countdownIsRunning = true;
        //const $countdownDiv = $('.countdown');
        //$countdownDiv.css('color', 'inherit');

        if (Countdown.s) {
            showElement(Countdown.s);
        }
        if (Countdown.m) {
            showElement(Countdown.m);
        }
        if (Countdown.h) {
            showElement(Countdown.h);
        }
        if (Countdown.d) {
            showElement(Countdown.d);
        }

        requestAnimationFrame(Countdown.calculate);
    }

    stop(ignoreCallback = false): void {
        Countdown.countdownIsRunning = false;
        Countdown.cleanUp();
        if (!ignoreCallback && Countdown.callback) {
            Countdown.callback();
            Countdown.callback = null;
        }
    }

    private static cleanUp() {
        document.body.style.removeProperty('background'); // color is defined in background-color
    }

    private static calculate(timestamp: DOMHighResTimeStamp): void {
        if (!Countdown.countdownIsRunning) {
            Countdown.countdownRuntimeIsRunning = false;
            return;
        }
        Countdown.countdownRuntimeIsRunning = true;
        if (Countdown.previousTimeStamp === null) {
            Countdown.previousTimeStamp = timestamp;
            Countdown.computeUpdate();
        }
        const tick = timestamp - Countdown.previousTimeStamp;

        if (tick >= 1000) {
            Countdown.computeUpdate();
            Countdown.previousTimeStamp = timestamp;
        }
        if (Countdown.timeRemaining > 0) {
            requestAnimationFrame(Countdown.calculate); // 10 Seconds
        } else {
            Countdown.countdownRuntimeIsRunning = false;
        }
    }

    private static computeUpdate() {
        console.debug("[Update timer]");
        const startDate: number = dayjs().toDate().getTime();
        Countdown.timeRemaining = (Countdown.endDateTime - startDate) / 1000;
        if (Countdown.timeRemaining > 0 && Countdown.countdownIsRunning) {
            document.body.style.background = `linear-gradient(90deg, #000000 ${100 - Countdown.timeRemaining / 9}%, #56647a 0%)`;
            if (Countdown.timeRemaining <= 300 /* 5 minutes */) {
                if (Countdown.s) {
                    (Countdown.s.parentNode as HTMLDivElement).style.color = 'red';
                }
            } else {
                if (Countdown.s) {
                    (Countdown.s.parentNode as HTMLDivElement).style.removeProperty('color');
                }
            }
            Countdown.days = Math.floor(Countdown.timeRemaining / 86400);
            Countdown.timeRemaining %= 86400;

            Countdown.hours = Math.floor(Countdown.timeRemaining / 3600);
            Countdown.timeRemaining %= 3600;
            if (Countdown.h && Countdown.hours < 1) {
                hideElement(Countdown.h);
            }
            if (Countdown.d && Countdown.days < 1) {
                hideElement(Countdown.d);
            }

            Countdown.minutes = Math.floor(Countdown.timeRemaining / 60);
            Countdown.timeRemaining %= 60;

            Countdown.seconds = Math.floor(Countdown.timeRemaining);

            if (Countdown.d) {
                Countdown.d.innerText = String(Countdown.days);
            }
            if (Countdown.h) {
                Countdown.h.innerText = String(Countdown.hours);
            }
            if (Countdown.m) {
                Countdown.m.innerText = addLeadingZero(Countdown.minutes);
            }
            if (Countdown.s) {
                Countdown.s.innerText = addLeadingZero(Countdown.seconds);
            }
        } else {
            // Time's up
            console.log("Countdown expired");
            Countdown.countdownIsRunning = false;
            Countdown.cleanUp();
            if (Countdown.callback) {
                Countdown.callback();
                Countdown.callback = null;
            }
            //setTimeout(evaluateEvents, 1200, true);
        }
    }
}