export default class _ {

  static data : Record<string,TranslationEntry> = {
    today: { en: 'today', de: 'heute', it: 'oggi' },
    tomorrow: { en: 'tomorrow', de: 'morgen', it: 'domani' },
  } as const;

  static _(key : string, lang : string) {
    return this.data[key]?.[lang] ?? key;
  }
}

declare interface TranslationEntry{
  en:string;
  de:string;
  it:string;
}
