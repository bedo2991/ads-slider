import { Manager, SlideEvents } from "../manager.js";
import Filter2 from "../modules/filters.js";
import { AdsEvent, Menu, MenuSimple, Product } from "../types.js";
import { addAnimationOnce } from "../utilities/animations.js";
import { Slide } from "./Slide.js";
import dayjs from 'dayjs/esm/index.js';

export class MenuSlide extends Slide {
    private slidesSpeedMs = 20000;

    private runningMenus: MenuSimple[] | null = [];
    private menusData: Menu[] = [];
    private events: AdsEvent[] = [];
    private filteredEvents: AdsEvent[] = [];

    private currentSlideIndex = -1;

    private slideInterval: ReturnType<typeof setInterval> | null = null;

    onStart(): void {
        super.onStart();
        const now = dayjs();
        this.displaySlide();
        this.filteredEvents = this.events.filter((event) =>
            now.isBetween(event.startDate, event.endDate)
        );
        this.currentSlideIndex = -1;
    }

    onResume(): void {
        super.onResume();
        if (this.filteredEvents.length === 0) {
            this.notifyMediator(SlideEvents.NOTHING);
            return;
        }

        this.runningMenus = this.filteredEvents[0].menus;
        if (!this.slideInterval) {
            this.slideInterval = setInterval(() => {
                this.nextMenu();
            }, this.slidesSpeedMs);
        }
        this.nextMenu();
    }

    loadMenu(menuID: number): Menu | null {
        const data = this.menusData.find((m) => m.id === menuID);
        if (!data) {
            this.notifyMediator(SlideEvents.ERROR);
            return null;
        }
        return data;
    }

    addMenu(menu: Menu) {
        this.menusData.push(menu);
    }

    hasMenu(menuID: number): boolean {
        return this.menusData.some((m) => m.id === menuID);
    }

    deleteMenu(menuID: number): void {
        this.menusData = this.menusData.filter((m) => m.id !== menuID);
    }

    private clearInterval(): void {
        if (this.slideInterval) {
            clearInterval(this.slideInterval);
            this.slideInterval = null;
        }
    }

    onStop(): void {
        super.onStop();
        this.clearInterval();
        this.hideSlide();
    }

    setEvents(events: AdsEvent[]) {
        console.log("[Menus] Set events");
        this.events = events;
        this.events = this.events.filter(Filter2.eventHasMenus);
        console.log(`[Menus] Got ${this.events.length} events`);
        this.notifyMediator(SlideEvents.DATA_UPDATED);
    }

    /**
     * @param menus 
     */
    setMenus(menus: Menu[]) {
        console.log("[Menus] Set menus");
        this.menusData = menus;
        console.log(`[Menus] Got ${this.menusData.length} menus`);
        this.notifyMediator(SlideEvents.DATA_UPDATED);
    }

    nextMenu() {
        console.log("[Menus] Next slide");
        if (this.runningMenus == null || this.runningMenus.length === 0) {
            this.notifyMediator(SlideEvents.NOTHING);
            return;
        }

        this.currentSlideIndex = this.currentSlideIndex + 1;
        if (this.currentSlideIndex >= this.runningMenus.length) {
            console.log("[Menus] end of list reached");
            this.notifyMediator(SlideEvents.CYCLE_END);
            return;
        }
        this.showMenu();
    }

    showMenu(): boolean {
        if (!this.runningMenus || !this.runningMenus[this.currentSlideIndex]) {
            throw new Error("Show menu failed");
        }
        const mc = document.getElementById('menu-container') as HTMLDivElement;
        const menu = this.loadMenu(this.runningMenus[this.currentSlideIndex].id);

        if (!menu) {
            return false;
        }

        const pr: Product[] = menu.products.filter(Filter2.isNotDisabled);

        if (pr.length === 0) {
            console.error('No items to show inside the menu', menu);
            this.notifyMediator(SlideEvents.ERROR);
            return false;
        }
        const menuName = mc.querySelector('.menu-name') as HTMLSpanElement;
        menuName.innerText = menu.category_name;
        addAnimationOnce(menuName, 'jackInTheBox');

        const icon = mc.querySelector('.menu-icon') as HTMLSpanElement;
        icon.removeAttribute('class');
        icon.classList.add('menu-icon', 'fas', `fa-${menu.icon ?? 'table-list'}`);
        if (Manager.Instance.areAnimationsEnabled()) {
            icon.classList.add('animate__animated', 'animate__pulse', 'animate__slow');
        }

        let extraRows = 0;
        mc.querySelectorAll('.menu_item').forEach((item) => {
            item.remove();
        });
        const rowTemplate = document.getElementById("menu-row") as HTMLTemplateElement;
        pr.forEach((product, i) => {
            let addLine = false;
            if (
                typeof product.price2 !== 'undefined'
                && typeof product.size2 !== 'undefined'
            ) {
                extraRows += 1;
                addLine = true;
            }
            const clone = rowTemplate.content.cloneNode(true) as DocumentFragment;
            const el = clone.querySelector('div') as HTMLDivElement;
            el.classList.toggle('menu_item_special', product.special === true);
            el.classList.toggle('odd', i % 2 === 1);
            el.innerText = product.name;
            const span = document.createElement('span') as HTMLSpanElement;
            span.innerHTML = `${product.size}&nbsp;&nbsp;${product.price
                .padStart(5, '%')
                .replaceAll('%', '&nbsp;')}${menu.currency}${(addLine && product.price2) ? `<br>${product.size2}&nbsp;&nbsp;${product.price2
                    .padStart(5, '%')
                    .replaceAll('%', '&nbsp;')}${menu.currency}`
                    : ''
                }`;
            el.appendChild(span);
            if (addLine) {
                el.appendChild(document.createElement('br'));
            }
            mc.appendChild(el);
        });
        console.debug('[Menu] Extra rows in the menu', extraRows);
        let fontSize = 47.0 / (pr.length + extraRows);
        if (fontSize > 7.5) {
            fontSize = 7;
        }

        (mc.querySelectorAll('.menu_item') as NodeListOf<HTMLDivElement>).forEach((item) => {
            item.style.fontSize = `${fontSize}vh`;
        });
        return true;
    }

    next(): void {
        this.nextMenu();
    }
}