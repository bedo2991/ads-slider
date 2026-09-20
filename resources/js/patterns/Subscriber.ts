export interface Subscriber {
    update(event: string): void;
}