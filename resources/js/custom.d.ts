interface JQuery {
    animateCss: (animationName: string, callback?: () => void) => JQuery;
    this: JQuery;
}


interface VideoSource extends HTMLElement {
    src: string;
    type: string;
}