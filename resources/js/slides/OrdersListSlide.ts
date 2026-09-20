import dayjs from 'dayjs/esm/index.js'
import { SlideEvents } from "../manager.js";
import { OrderslistData, OrdersListDataWihthTimestamp, OrderslistItem } from "../types.js";
import { Slide } from "./Slide.js";

export class OrdersListSlide extends Slide {
    private showSlideFor = 20000;

    private static readonly animationClasses = [
        "animate__animated",
        "animate__tada",
        "animate__repeat-3"
    ];

    private data: OrdersListDataWihthTimestamp | null = null;
    private previous_data: OrdersListDataWihthTimestamp | null = null;
    private slideTimeout: ReturnType<typeof setTimeout> | null = null;

    private dataInitialized = false;
    private updateDataTimestamp: string | null = null;

    onCreate(): void {
        super.onCreate();
        // Test data, remove later
        /*
        this.dataInitialized = true;
        this.data = {
            important: false,
            counters: [{
                label: "Bestellungen heute",
                value: 23
            },
                //{
                //    label: "Countries",
                //    value: 2
                //}
            ],
            pending_title: "Neue Bestellungen",
            fulfilled_title: "Fast fertig!",
            pickup_title: "Abholbereit!",
            pending: [
                { label: "AGS", timestamp: "2025-06-02T20:12:57.000Z" },
                { label: "DPS", timestamp: "2025-06-02T20:10:57.000Z" }
            ],
            fulfilled: [
                { label: "FUL", timestamp: "2025-06-02T20:12:57.000Z" },
            ],
            pickup: [
                { label: "PIC", timestamp: "2025-06-04T20:12:57.000Z" },
                { label: "ZDA", timestamp: "2025-06-02T20:12:57.000Z" },
                { label: "AIF", timestamp: "2025-06-02T20:12:57.000Z" },
                { label: "EWQ", timestamp: "2025-06-02T20:12:57.000Z" },
                { label: "TZH", timestamp: "2025-06-02T20:12:57.000Z" },
                { label: "SFH", timestamp: "2025-06-02T20:12:57.000Z" },
                { label: "SFR", timestamp: "2025-06-02T20:12:57.000Z" },
                { label: "ANR", timestamp: "2025-06-02T20:12:57.000Z" },
            ]
        };
        */
    }

    onStart(): void {
        super.onStart();
        this.displaySlide();
    }

    onResume(): void {
        super.onResume();
        if (!this.data) {
            this.notifyMediator(SlideEvents.NOTHING);
            return;
        }

        if (!this.dataInitialized) {
            this.notifyMediator(SlideEvents.ERROR);
            return;
        }

        // Place the last received data into the slide.
        // We do it here because we want to compare the last shown data with the new data
        if (!this.arrangeData()) {
            this.notifyMediator(SlideEvents.ERROR);
            return;
        }

        if (!this.slideTimeout) {
            this.slideTimeout = setTimeout(() => {
                this.notifyMediator(SlideEvents.DONE);
            }, this.showSlideFor);
        }
    }

    updateData(data: OrderslistData): void {
        if (!this.dataInitialized) {
            // First initialization
            this.data = <OrdersListDataWihthTimestamp>data;
            this.data.timestamp = dayjs().toISOString();
            this.data.pending.forEach(element => {
                element.status = "new";
            });
            this.data.fulfilled.forEach(element => {
                element.status = "new";
            });
            this.data.pickup.forEach(element => {
                element.status = "new";
            });
            this.dataInitialized = true;
        }
        else {
            // Data updated
            this.previous_data = this.data;
            this.data = <OrdersListDataWihthTimestamp>data;
            this.data.timestamp = dayjs().toISOString();
            this.decideStatus(this.data.pending, 'pen');
            this.decideStatus(this.data.fulfilled, 'ful');
            this.decideStatus(this.data.pickup, 'pick');
        }
    }

    private decideStatus(list: OrderslistItem[], cat: ('ful' | 'pick' | 'pen')): void {
        if (!this.previous_data || !list || list.length === 0) {
            return;
        }
        list.forEach(element => {
            // We use the HTML elements instead of previous_data because 
            // we want to check if the change was already displayed.
            const el: HTMLSpanElement | null = document.getElementById(`ol-item-${cat}-${element.label}`);
            if (!el) {
                element.status = "new";
            }
        });
    }

    private getListItem(el: OrderslistItem, cat: ('ful' | 'pick' | 'pen')): string {
        if (el.status === "new") {
            return `<span id="ol-item-${cat}-${el.label}" class="ol-item ${OrdersListSlide.animationClasses.join(' ')}">
                ${el.label}
                </span>`;
        }
        return `<span id="ol-item-${el.label}" class="ol-item">
                ${el.label}
                </span>` ;
    }

    private arrangeData(): boolean {
        if (!this.data) {
            return false;
        }
        // only arrange data if the timestamp has changed
        if (this.updateDataTimestamp == this.data.timestamp)
            return true;
        this.updateDataTimestamp = this.data.timestamp ?? null;
        if (this.data.pending_title) {
            const pendingTitle = <HTMLDivElement>document.querySelector("#ol-pending h2");
            pendingTitle.innerText = this.data.pending_title;
        }

        if (this.data.fulfilled_title) {
            const fulfilledTitle = <HTMLDivElement>document.querySelector("#ol-fulfilled h2");
            fulfilledTitle.innerText = this.data.fulfilled_title;
        }
        if (this.data.pickup_title) {
            const pickupTitle = <HTMLDivElement>document.querySelector("#ol-pickup h2");
            pickupTitle.innerText = this.data.pickup_title;
        }
        const olPending = <HTMLDivElement>document.querySelector("#ol-pending div.orderslist");
        olPending.innerHTML = this.data.pending.length === 0 ? "---" : this.data.pending
            .filter(el => !!el.label)
            .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
            .map(el => this.getListItem(el, 'pen'))
            .join(' ');

        const olFulfilled = <HTMLDivElement>document.querySelector("#ol-fulfilled div.orderslist");
        olFulfilled.innerHTML = this.data.fulfilled.length === 0 ? "---" : this.data.fulfilled
            .filter(el => !!el.label)
            .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
            .map(el => this.getListItem(el, 'ful'))
            .join(' ');

        const olPickup = <HTMLDivElement>document.querySelector("#ol-pickup div.orderslist");
        olPickup.innerHTML = this.data.pickup.length === 0 ? "---" : this.data.pickup
            .filter(el => !!el.label)
            .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
            .map(el => this.getListItem(el, 'pick'))
            .join(' ');

        const counters = <HTMLDivElement>document.querySelector("#ol-counters");
        counters.innerHTML = "";
        if (this.data.counters) {
            this.data.counters.forEach(counter => {
                counters.innerHTML += (counter.label + ": " + counter.value + " ");
            });
        }

        return true;
    }

    onStop(): void {
        super.onStop();
        this.clearTimeout();
        this.hideSlide();

        // remove all animated classes
        const animatedElements = document.querySelectorAll(".ol-item");
        animatedElements.forEach(el => {
            el.classList.remove(...OrdersListSlide.animationClasses);
        });
    }

    private clearTimeout() {
        if (this.slideTimeout) {
            clearTimeout(this.slideTimeout);
            this.slideTimeout = null;
        }
    }

    next(): void {
        this.clearTimeout();
        this.notifyMediator(SlideEvents.DONE);
    }
}