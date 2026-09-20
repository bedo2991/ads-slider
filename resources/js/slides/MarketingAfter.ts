import { MiniSlide } from "./MiniSlides.js";
import { UnskippableSlide } from "./UnskippableSlide.js";

export class MarketingAfter extends UnskippableSlide {
    private interval: ReturnType<typeof setInterval> | null = null;
    private sentenceHolder: HTMLDivElement | null = null;
    onCreate(): void {
        super.onCreate();
        console.log("MarketingAfter created");
        this.sentenceHolder = document.getElementById('mitgliederWerbung') as HTMLDivElement;
    }

    onStart(): void {
        super.onStart();
        this.displaySlide();
        MiniSlide.Instance.show();
        console.log("MarketingAfter started");
    }

    onResume(): void {
        super.onResume();
        this.startInterval();
        console.log("MarketingAfter resumed");
        console.debug("[30Mins] Showing 'thirty minutes after'");
    }

    onPause(): void {
        super.onPause();
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
        console.log("MarketingAfter paused");
    }

    onStop(): void {
        super.onStop();
        this.hideSlide();
        MiniSlide.Instance.hide();
        console.log("MarketingAfter stopped");
    }

    onRestart(): void {
        super.onRestart();
        this.displaySlide();
        console.log("MarketingAfter restarted");
    }

    onDestroy(): void {
        super.onDestroy();
        this.sentenceHolder = null;
        this.div.remove();
        console.log("MarketingAfter destroyed");
    }

    private startInterval() {
        if (this.interval === null) {
            this.interval = setInterval(() => { this.nextSlide() }, 25000);
            this.nextSlide();
        }
    }

    nextSlide(): void {
        console.log("Next sentence", this.sentenceHolder);
        if (this.sentenceHolder)
            this.sentenceHolder.innerText = this.getSentence();
    }

    next(): void {
        this.nextSlide();
        MiniSlide.Instance.next();
    }

    getSentence(): string {
        return this.sentences[Math.floor(Math.random() * this.sentences.length)];
    }

    private sentences: string[] = [
        'Lust hinter der Theke zu stehen? Komm zur Versammlung vorbei!',
        'Would you like to try working behind the bar? Visit us during our weekly meeting!',
        'Wärst du gerne länger geblieben? Werde Mitglied und ändere das!',
        'Would you have stayed longer? Become a member and make that happen.',
        'Der Club wird jetzt aufgeräumt. Wie wäre es mit ein bisschen helfen?',
        'We are going to clean up the club now, how about helping a little bit?',
        'Hast du noch alles dabei? Handy, Schlüssel, Brille, Gute Laune, Würde…',
        'Do you still have everything? Mobile phone, keys, glasses, good mood, dignity…',
        'Schon gewusst? Wir arbeiten hier freiwillig und kriegen kein Geld.',
        "Did you know? We all work here voluntarily and don't earn any money.",
        "Kein Alkohol am Steuer! - Don't drink and drive!",
        'Ja, das hier zu lesen ist lustig, du sollst aber bestimmt nach Hause…',
        'Sure, reading these messages is fun, but I guess you should probably go home now…',
        'Bis ganz zum Ende geblieben? Du bist der perfekte Kandidat für uns!',
        'Did you remain until the end? You are the perfect candidate for us!',
        'Hier könnte ein emotionaler Text stehen. Tut es aber nicht.',
    ];
}