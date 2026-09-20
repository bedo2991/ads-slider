/**
 *
 * @param {string} text The text to fix
 * @param {string} tag The tag to look for
 * @param {string} open The tag should be replaced with this string, when it starts
 * @param {string} close The tag should be replaced with this string, when it ends
 * @returns {string}
 */
export function replaceTag(text: string, tag: string, open: string, close: string): string {
    const subStrings = text.split(tag);
    let res = '';
    if (subStrings.length % 2 === 1) {
        for (let i = 0; i < subStrings.length; i += 1) {
            res += subStrings[i];
            if (i + 1 < subStrings.length) {
                res += (i % 2 === 0 ? open : close);
            }
        }
    } else return text;
    return res;
}

export function hideElement(el: HTMLElement) {
    el.style.display = 'none';
}

export function showElement(el: HTMLElement) {
    el.style.removeProperty('display');
}

export function applyMarkdown(htmlString: string): string {
    let res = htmlString;

    // Asterisks to bold
    res = replaceTag(res, '*', '<b>', '</b>');

    // _ to italics
    res = replaceTag(res, '_', '<i>', '</i>');

    // ~ to strike through
    res = replaceTag(res, '~', '<s>', '</s>');

    // ``` to strike through
    res = replaceTag(res, '```', '<tt>', '</tt>');

    // Newline \n to <br>
    res = res.replace(/\\n/g, '<br>');
    return res;
}

export function fillInComponentSafe(component: HTMLElement, s: string): void {
    component.innerText = s;
    component.innerHTML = applyMarkdown(component.innerHTML);
}

export function fillInComponentSafeStripeNewlines(component: HTMLElement, s: string): void {
    fillInComponentSafe(component, s);
    component.innerHTML = component.innerHTML.replaceAll('<br>', '');
}

export function autosizeText(el: HTMLElement): void {
    //Restore the maximum font size:
    let fontSize = 300;
    el.style.fontSize = `${fontSize}px`;
    el.style.textOverflow = 'clip';
    let steps = 1;
    // if it doesn't fit, divide it by 2
    while (el.scrollHeight > el.offsetHeight || el.scrollWidth > el.offsetWidth) {
        fontSize = ~~(fontSize / 2);
        el.style.fontSize = `${fontSize}px`;
        console.log(steps++, fontSize);
    }
    // If it was divided at least once
    if (steps > 1) {
        // Increase the font by 3 until it doesn't fit again
        while (el.scrollHeight <= el.offsetHeight && el.scrollWidth <= el.offsetWidth) {
            fontSize = fontSize + 3;
            el.style.fontSize = `${fontSize}px`;
            console.log(steps++, fontSize);
        }
        // Make it fit again by removing 1
        while (el.scrollHeight > el.offsetHeight || el.scrollWidth > el.offsetWidth) {
            fontSize = fontSize - 1;
            el.style.fontSize = `${fontSize}px`;
            console.log(steps++, fontSize);
        }
    }
    el.style.textOverflow = 'ellipses';
}