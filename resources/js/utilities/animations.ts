import { Manager } from "../manager.js";
import { showElement } from "./misc.js";

export function addAnimation(el: HTMLElement, animationName: string) {
    if (!Manager.Instance.areAnimationsEnabled()) return;

    el.classList.forEach((c) => {
        if (c.startsWith('animate__')) {
            el.classList.remove(c);
        }
    });
    el.classList.add('animate__animated', `animate__${animationName}`);
}

export function addAnimationOnce(el: HTMLElement, animationName: string, callback?: () => void) {
    if (el.style.display === "none") {
        showElement(el);
    }

    if (!Manager.Instance.areAnimationsEnabled()) {
        if (callback) callback();
        return;
    }

    el.addEventListener('animationend', (event: AnimationEvent) => {
        event.stopPropagation();

        if (animationName.includes('Out')) {
            const target = event.currentTarget;
            if (target instanceof HTMLElement) {
                if (target.classList.contains('event-details')) {
                    target.style.display = "none";
                    (document.getElementById('left-part-container') as HTMLDivElement).style.display = "none";
                }
            }
        }

        el.classList.remove('animate__animated', `animate__${animationName}`);
        if (callback) callback();
    }, { once: true });

    addAnimation(el, animationName);
}