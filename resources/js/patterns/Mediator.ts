import { Component } from "./Component.js";

export interface Mediator {
    notify(source: Component, event: string): void;
}