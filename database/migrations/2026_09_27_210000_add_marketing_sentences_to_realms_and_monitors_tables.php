<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->json('marketing_sentences')->nullable()->after('name');
        });

        Schema::table('monitors', function (Blueprint $table) {
            $table->json('marketing_sentences')->nullable()->after('show_we_are_closed_marketing');
        });

        $defaultSentences = [
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

        DB::table('realms')->update(['marketing_sentences' => json_encode($defaultSentences)]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('marketing_sentences');
        });

        Schema::table('realms', function (Blueprint $table) {
            $table->dropColumn('marketing_sentences');
        });
    }
};
