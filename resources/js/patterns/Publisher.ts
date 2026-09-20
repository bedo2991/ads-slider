import { Subscriber } from "./Subscriber.js";

export interface Publisher {
    subscribe(subscriber: Subscriber): void;
    unsubscribe(subscriber: Subscriber): void;
    notifySubscribers(event: string): void;
}