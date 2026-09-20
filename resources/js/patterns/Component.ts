import { Mediator } from "./Mediator.js";

/**
 * Mediator pattern: https://refactoring.guru/design-patterns/mediator
 */
export abstract class Component {
    private mediator: Mediator;
    constructor(mediator: Mediator) {
        this.mediator = mediator;
    }

    protected notifyMediator(event: string) {
        this.mediator.notify(this, event);
    }
}