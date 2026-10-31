"""Page content: the argument, the response, resources, about, and the
auxiliary pages (Why Islam, sin and repentance, an invitation, Paul)."""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from blocks import AYAH, B, BUTTON, BUTTONS, COL, COLS, G, H, LIST, P, QUOTE, REF, SC

R = REF
IMG = "/wp-content/themes/islamicdilemma/assets/img/"

# Arabic texts used on more than one page
Q = {
    "3:3": "نَزَّلَ عَلَيْكَ الْكِتَابَ بِالْحَقِّ مُصَدِّقًا لِّمَا بَيْنَ يَدَيْهِ وَأَنزَلَ التَّوْرَاةَ وَالْإِنجِيلَ",
    "5:44": "بِمَا اسْتُحْفِظُوا مِن كِتَابِ اللَّهِ وَكَانُوا عَلَيْهِ شُهَدَاءَ",
    "5:47": "وَلْيَحْكُمْ أَهْلُ الْإِنجِيلِ بِمَا أَنزَلَ اللَّهُ فِيهِ",
    "5:48": "وَأَنزَلْنَا إِلَيْكَ الْكِتَابَ بِالْحَقِّ مُصَدِّقًا لِّمَا بَيْنَ يَدَيْهِ مِنَ الْكِتَابِ وَمُهَيْمِنًا عَلَيْهِ",
    "5:68": "قُلْ يَا أَهْلَ الْكِتَابِ لَسْتُمْ عَلَىٰ شَيْءٍ حَتَّىٰ تُقِيمُوا التَّوْرَاةَ وَالْإِنجِيلَ وَمَا أُنزِلَ إِلَيْكُم مِّن رَّبِّكُمْ",
    "10:94": "فَإِن كُنتَ فِي شَكٍّ مِّمَّا أَنزَلْنَا إِلَيْكَ فَاسْأَلِ الَّذِينَ يَقْرَءُونَ الْكِتَابَ مِن قَبْلِكَ",
    "15:9": "إِنَّا نَحْنُ نَزَّلْنَا الذِّكْرَ وَإِنَّا لَهُ لَحَافِظُونَ",
    "18:27": "وَاتْلُ مَا أُوحِيَ إِلَيْكَ مِن كِتَابِ رَبِّكَ لَا مُبَدِّلَ لِكَلِمَاتِهِ وَلَن تَجِدَ مِن دُونِهِ مُلْتَحَدًا",
    "29:46": "وَقُولُوا آمَنَّا بِالَّذِي أُنزِلَ إِلَيْنَا وَأُنزِلَ إِلَيْكُمْ وَإِلَٰهُنَا وَإِلَٰهُكُمْ وَاحِدٌ وَنَحْنُ لَهُ مُسْلِمُونَ",
    "2:75": "وَقَدْ كَانَ فَرِيقٌ مِّنْهُمْ يَسْمَعُونَ كَلَامَ اللَّهِ ثُمَّ يُحَرِّفُونَهُ مِن بَعْدِ مَا عَقَلُوهُ وَهُمْ يَعْلَمُونَ",
    "2:79": "فَوَيْلٌ لِّلَّذِينَ يَكْتُبُونَ الْكِتَابَ بِأَيْدِيهِمْ ثُمَّ يَقُولُونَ هَٰذَا مِنْ عِندِ اللَّهِ",
    "112": "قُلْ هُوَ اللَّهُ أَحَدٌ ۝ اللَّهُ الصَّمَدُ ۝ لَمْ يَلِدْ وَلَمْ يُولَدْ ۝ وَلَمْ يَكُن لَّهُ كُفُوًا أَحَدٌ",
    "39:53": "قُلْ يَا عِبَادِيَ الَّذِينَ أَسْرَفُوا عَلَىٰ أَنفُسِهِمْ لَا تَقْنَطُوا مِن رَّحْمَةِ اللَّهِ ۚ إِنَّ اللَّهَ يَغْفِرُ الذُّنُوبَ جَمِيعًا",
    "2:186": "وَإِذَا سَأَلَكَ عِبَادِي عَنِّي فَإِنِّي قَرِيبٌ ۖ أُجِيبُ دَعْوَةَ الدَّاعِ إِذَا دَعَانِ",
    "4:48": "إِنَّ اللَّهَ لَا يَغْفِرُ أَن يُشْرَكَ بِهِ وَيَغْفِرُ مَا دُونَ ذَٰلِكَ لِمَن يَشَاءُ",
    "5:72": "وَقَالَ الْمَسِيحُ يَا بَنِي إِسْرَائِيلَ اعْبُدُوا اللَّهَ رَبِّي وَرَبَّكُمْ",
    "5:74": "أَفَلَا يَتُوبُونَ إِلَى اللَّهِ وَيَسْتَغْفِرُونَهُ ۚ وَاللَّهُ غَفُورٌ رَّحِيمٌ",
    "3:64": "قُلْ يَا أَهْلَ الْكِتَابِ تَعَالَوْا إِلَىٰ كَلِمَةٍ سَوَاءٍ بَيْنَنَا وَبَيْنَكُمْ أَلَّا نَعْبُدَ إِلَّا اللَّهَ وَلَا نُشْرِكَ بِهِ شَيْئًا",
}


def sidebar(*boxes):
    return COL("36%", G("sidebar", SC("[islamicdilemma_toc]"), *boxes))


def side_box(title, *paras):
    return G("is-style-side-box", H(2, title, "side-title"), *paras)


def gloss(label, text):
    return P(f"<strong>{label}</strong> {text}", "gloss")


def article(main, side):
    return [COLS("article-grid", COL("64%", *main), side, align="wide")]


def notes(items):
    return [H(2, "Notes", "notes-title"), LIST(items, ordered=True, c="is-style-notes")]


PAGES = []


def page(slug, title, excerpt, blocks, order):
    PAGES.append({"slug": slug, "title": title, "excerpt": excerpt, "blocks": blocks, "order": order})


# ---------------------------------------------------------------- Home (static front page; template supplies content)
page("home", "Home", "", [], 0)
page("enrichment", "Enrichment", "Commentary, close readings and replies.", [], 4)

# ---------------------------------------------------------------- The argument, stated fairly
page("islamic-dilemma", "Understanding the Islamic Dilemma",
     "The argument as its advocates present it: its premises, the verses it cites, and the choice it asks Muslims to make.",
     article([
         P("A reply is only worth reading if it answers the argument its opponents actually make. This page states that argument as its "
           "advocates present it, in their order and with their verses, before the <a href=\"/muslim-response/\">Muslim response</a> examines it.",
           "is-style-lead"),
         H(2, "The argument in brief", anchor="claim"),
         P("Its advocates sum it up in one sentence: if the Gospel is trustworthy, Islam is false because it contradicts the Gospel; if the "
           "Gospel is corrupted, Islam is false because the Qur’an affirms the Gospel’s authority. Set out in steps, it runs:"),
         LIST(["The Qur’an says God revealed the Torah and the Gospel.",
               "The Gospel it means is the one Christians held in the Prophet’s day, which is the New Testament.",
               "The Qur’an tells Christians to judge by that Gospel and to uphold it.",
               "The New Testament teaches that Jesus died, rose again and is Lord, which the Qur’an denies.",
               "So either the Gospel is true and Islam is false, or the Gospel is corrupted and the Qur’an was wrong to affirm it."], ordered=True),
         H(2, "The verses its advocates cite", anchor="verses"),
         P("These are the passages the argument rests on, quoted in full because the response depends on reading them closely."),
         AYAH(Q["3:3"], "He has sent down to you the Book in truth, confirming what came before it, and He sent down the Torah and the Gospel.", "Q 3:3"),
         P("Read as proof that the Qur’an treats the Gospel as revelation."),
         AYAH(Q["29:46"], "Say: we believe in what was sent down to us and what was sent down to you; our God and your God is one, and to Him we submit.", "Q 29:46"),
         P("Read as a command to believe in the Bible Christians hold, which, the argument says, Muslims then fail to do."),
         AYAH(Q["5:47"], "Let the people of the Gospel judge by what God has revealed therein.", "Q 5:47"),
         P("Read as a command to judge by the Gospel Christians held at the time."),
         AYAH(Q["5:68"], "Say: People of the Book, you have nothing to stand on until you uphold the Torah, the Gospel and what has been sent down to you from your Lord.", "Q 5:68"),
         P(f"Read, with {R('Q 5:66')}, as telling Christians to hold to the Bible they have."),
         AYAH(Q["10:94"], "If you are in doubt about what We have sent down to you, ask those who read the scripture before you.", "Q 10:94"),
         P("Read as sending the Prophet to the scripture of his day for confirmation."),
         AYAH(Q["18:27"], "Recite what has been revealed to you of the Book of your Lord. None can change His words.", "Q 18:27"),
         P("Read as ruling out any corruption of earlier revelation, since that too was God’s word."),
         H(2, "The follow-up questions", anchor="questions"),
         P("When Muslims answer, the argument’s advocates reply with a set of questions. We list them because each one is answered on this site."),
         LIST(['“If the Injil was a different book, where is it, and who quoted it?” <a href="/injil-where-is-it/">Answered here</a>',
               '“When was the Bible corrupted, by whom, and where is the original?” <a href="/how-quran-describes-alteration/">Answered here</a>',
               '“If the scriptures were corrupted, why does the Qur’an call them guidance and light?” <a href="/every-verse-on-gospel/">Answered here</a>',
               '“Why were Muslims told to say ‘we believe in what was revealed to you’?” <a href="/we-believe-in-what-was-revealed-to-you/">Answered here</a>',
               '“How can scripture be corrupted if no one can change God’s words?” <a href="/none-can-change-his-words/">Answered here</a>',
               '“Why was the Prophet told to ask the People of the Book?” <a href="/ask-those-who-read-scripture/">Answered here</a>']),
         H(2, "Where it leads", anchor="leads"),
         P("Its advocates are open that the dilemma is meant to move the conversation to Jesus: whether he died on the cross, rose, is divine, and "
           "how sins are forgiven. Those are real questions, and this site answers them too, in "
           "<a href=\"/three-contrasts-three-misquotations/\">the contrasts the argument draws</a>, "
           "<a href=\"/sin-and-repentance/\">sin and repentance in Islam</a> and <a href=\"/paul-and-message-of-jesus/\">Paul and the message of Jesus</a>."),
         P('<a class="textlink" href="/muslim-response/">Read the Muslim response →</a>'),
     ], sidebar(side_box("Notes",
                         gloss("Fairness", "This summary follows the argument as its advocates present it, online and in public debates. Corrections are welcome on the <a href=\"/about/\">About</a> page."),
                         gloss("Translations", "Verse translations here are the site’s own. Advocates of the argument use several published translations; the meaning at issue is the same.")))),
     1)

# ---------------------------------------------------------------- The response
page("muslim-response", "The Islamic Dilemma, answered in full",
     "The argument needs the Qur’an to hold one of two positions about the Bible. Read in Arabic and in context, it holds neither.",
     [B("image", {"url": IMG + "konya-manuscript.jpg", "alt": "Gold-illuminated pages of an old Qur’an manuscript in Konya, Türkiye",
                  "caption": 'An illuminated Qur’an manuscript, Konya. Photo: <a href="https://www.pexels.com/photo/a-close-up-of-an-old-quran-book-27980497/">astar / Pexels</a>',
                  "align": "wide", "className": "plate wide", "aspectRatio": "16/6", "scale": "cover", "sizeSlug": "full"})] +
     article([
         P("The argument known as the Islamic Dilemma is short, which is much of its appeal. The Qur’an, it says, affirms the Torah and the Gospel "
           "as revelation. Either Christians in the Prophet’s time possessed those books intact, in which case the Qur’an is contradicted by them, "
           "or they did not, in which case the Qur’an was wrong to commend them. Both paths, it concludes, end with Islam refuted.", "has-drop-cap"),
         P("Of its steps (set out on the <a href=\"/islamic-dilemma/\">argument page</a>), the first is true. The second is false, the third does not "
           "follow from the verse it cites, and the hidden premise behind the whole argument, that God promised to preserve every earlier book, is "
           "one the Qur’an never makes. This reply takes each in turn, then answers the follow-up questions."),
         H(2, f"1. What the Qur’an affirms {R('Q 3:3–4')}", anchor="affirm"),
         P(f"The Qur’an says God “sent down the Torah and the Gospel before, as guidance for people” {R('Q 3:3–4')}. Muslims accept this without "
           "reservation; belief in the earlier books is an article of faith.<sup class=\"n\"><a href=\"#n1\" id=\"r1\">1</a></sup> The dispute is over "
           "what those names refer to, and what state the Qur’an says the books were in."),
         H(2, f"2. One Injil, given to Jesus {R('Q 5:46')}", anchor="injil"),
         P(f"The Qur’an describes the Injil as something God gave to Jesus: “We gave him the Gospel, in which is guidance and light” {R('Q 5:46')}. "
           f"God “will teach him the Book, wisdom, the Torah and the Gospel” {R('Q 3:48')}. It is a revelation received by a prophet, as the Torah was "
           "given to Moses and the Qur’an to Muhammad."),
         P("The New Testament does not describe itself this way. Its four Gospels are accounts of Jesus written by others in the decades after him, "
           "and the other twenty-three books are letters, a history and an apocalypse. A book <em>about</em> Jesus is a different thing from the "
           "revelation <em>given to</em> him. The second premise treats them as identical, and the dilemma depends on that move. "
           "<a href=\"/injil-where-is-it/\">Where is the Injil?</a> answers the follow-up question in full."),
         H(2, f"3. “Let them judge by what God revealed therein” {R('Q 5:47')}", anchor="judge"),
         AYAH(Q["5:47"], "Let the people of the Gospel judge by what God has revealed therein.", "Q 5:47"),
         P("The command is to judge by <em>mā anzala Allāhu fīhi</em>, “what God revealed in it.” It points to revelation that remains in what they "
           "hold. It says nothing about whether everything they hold is revelation. A Muslim can urge a Christian to follow Jesus’ command to love "
           "God with all one’s heart without vouching for every verse of the New Testament."),
         P(f"The surrounding passage rules out the stronger reading. A few verses earlier the Qur’an describes some of the same communities as people "
           f"“who distort words from their places” {R('Q 5:41')}, and earlier in the same surah it says of the Christians that “they forgot a portion "
           f"of what they were reminded of” {R('Q 5:14')}. The very next verse tells the Prophet to judge between them by what God revealed to him, "
           f"the Qur’an, “and do not follow their desires” {R('Q 5:48')}. A passage that accuses a community of distortion and sends its judgment to "
           "the Qur’an cannot be certifying that community’s copy of its scripture as flawless."),
         P("The Gospels contain the same pattern. Jesus tells his hearers to obey the teachers of the Law and, in the same speech, condemns them:"),
         QUOTE("“The scribes and the Pharisees sit on Moses’ seat, so do and observe whatever they tell you, but not the works they do.”", "Matthew 23:2–3 · ESV"),
         P("Nobody concludes from this that Jesus thought the scribes were infallible. The Qur’an is reasoning the same way. "
           "<a href=\"/judge-by-it-uphold-it/\">Judge by it, uphold it</a> reads 5:47, 5:66 and 5:68 in full."),
         H(2, f"4. Entrusted, not guarded {R('Q 5:44 · 15:9')}", anchor="trust"),
         P("The hidden premise is that the Qur’an promises to preserve every revealed book. It does not. Of the Torah, it says the rabbis and scholars judged"),
         AYAH(Q["5:44"], "by what they were entrusted to preserve of the Book of God, and they were witnesses to it.", "Q 5:44"),
         P("The verb <em>istuḥfiẓū</em> is passive: they were <em>asked to keep</em> it. Preservation was a duty placed on people. Of the Qur’an alone the "
           "language changes, and God takes the duty on Himself:"),
         AYAH(Q["15:9"], "It is We who sent down the Reminder, and it is We who will guard it.", "Q 15:9"),
         P(f"A trust held by people can be broken by people, and the Qur’an says that in places it was {R('Q 2:79')}. That is not God failing. In the "
           "Qur’an’s own account, it is why a final revelation was sent with a different guarantee.<sup class=\"n\"><a href=\"#n2\" id=\"r2\">2</a></sup> "
           f"As for “none can change His words” {R('Q 18:27 · 6:115')}, both verses speak of what God decrees and promises, and 18:27 is about the "
           "Qur’an itself. <a href=\"/none-can-change-his-words/\">None can change His words</a> sets out the evidence."),
         H(2, f"5. The position the dilemma leaves out {R('Q 5:48')}", anchor="muhaymin"),
         AYAH(Q["5:48"], "We have sent down to you the Book in truth, confirming the scripture that came before it and as a guardian over it.", "Q 5:48"),
         P("The Qur’an gives itself two roles toward earlier scripture. It is <em>muṣaddiq</em>, confirming, and <em>muhaymin</em>, a guardian or judge that "
           "decides what in the earlier record is authentic.<sup class=\"n\"><a href=\"#n3\" id=\"r3\">3</a></sup> Both roles only make sense if earlier "
           "scripture survives in part. If it were wholly intact there would be nothing to guard; if wholly lost, nothing to confirm."),
         P("That third option is the Qur’an’s stated view, not a later rescue. It was also the view of classical scholars. Ibn Taymiyyah’s "
           "<em>al-Jawāb al-Ṣaḥīḥ</em>, a fourteenth-century reply to a Christian treatise, discusses whether the alteration was of wording, of meaning "
           "or both, and holds that both occurred in places while much of the text remained.<sup class=\"n\"><a href=\"#n4\" id=\"r4\">4</a></sup>"),
         H(2, "6. The follow-up questions, answered", anchor="follow-up"),
         P("When the core argument fails, its advocates turn to questions. Each has a short answer here and a full one in Enrichment."),
         LIST(["<strong>“Where is the Injil, and who quoted it?”</strong> The Gospels themselves say Jesus went about “proclaiming the gospel” "
               f"{R('Mark 1:14–15')} before any of them was written. That message is the Injil. <a href=\"/injil-where-is-it/\">Read more</a>",
               "<strong>“When, how and by whom was it corrupted?”</strong> The Qur’an names the means: writing by hand and calling it scripture, "
               f"twisting the tongue, concealing, and moving words from their places {R('Q 2:79 · 3:78 · 5:13')}. "
               "<a href=\"/how-quran-describes-alteration/\">Read more</a>",
               "<strong>“Why does the Qur’an call those books guidance and light?”</strong> Because they were, as revealed. Every one of those verses "
               "speaks of the revelation as given, not of the copies in circulation. <a href=\"/every-verse-on-gospel/\">Read more</a>",
               f"<strong>“You are told to believe in what was revealed to Christians.”</strong> Yes: what was <em>revealed</em> {R('Q 29:46')}, "
               "which is not the same as everything later written. <a href=\"/we-believe-in-what-was-revealed-to-you/\">Read more</a>",
               f"<strong>“The Prophet was told to ask the People of the Book.”</strong> “If you are in doubt” {R('Q 10:94')} is a conditional, and "
               "the verse answers itself: “the truth has come to you.” <a href=\"/ask-those-who-read-scripture/\">Read more</a>",
               "<strong>“The Bible and the Qur’an teach different things about Jesus.”</strong> They do, and the Qur’an says so. That is the "
               "<em>muhaymin</em> role, not a contradiction. <a href=\"/three-contrasts-three-misquotations/\">Read more</a>"]),
         G("is-style-callout", P("<strong>Conclusion.</strong> The dilemma offers two options: wholly preserved or wholly corrupted. The Qur’an holds a "
                                 "third, partly preserved and judged by the final revelation, and says so in the same passage the argument quotes. "
                                 "A dilemma that leaves out its opponent’s actual position does not refute it.")),
         *notes(["Belief in the books is among the articles of faith in the Ḥadīth of Jibrīl, <em>Ṣaḥīḥ Muslim</em>, Book of Faith. <a href=\"#r1\">↩</a>",
                 "On the contrast between 5:44 and 15:9, see the commentaries of al-Ṭabarī and al-Rāzī on both verses. <a href=\"#r2\">↩</a>",
                 "Ibn ʿAbbās, as reported by al-Ṭabarī, glosses <em>muhaymin</em> as <em>al-amīn</em>, the trustee, and as <em>shāhid</em>, the witness. <a href=\"#r3\">↩</a>",
                 "Ibn Taymiyyah, <em>al-Jawāb al-Ṣaḥīḥ li-man Baddala Dīn al-Masīḥ</em>, a reply to the <em>Letter to a Muslim Friend</em> of Paul of Antioch "
                 "in its Cypriot recension. Partial English translation: Thomas F. Michel, <em>A Muslim Theologian’s Response to Christianity</em> (1984). <a href=\"#r4\">↩</a>"]),
     ], sidebar(side_box("Margin notes",
                         gloss("Terms", "<em>Taḥrīf</em> means alteration. Classical writers distinguish <em>taḥrīf al-lafẓ</em>, changing the wording, from <em>taḥrīf al-maʿnā</em>, changing the meaning through interpretation."),
                         gloss("On §2", "The Qur’an never names Matthew, Mark, Luke or John, and never speaks of four Gospels. It always says <em>al-Injīl</em>, singular."),
                         gloss("On §3", f"Some commentators read Q 5:47 as an instruction to judge by the Injil’s confirmation of a coming prophet {R('Q 7:157')}."),
                         gloss("On §4", "Jeremiah 8:8 makes the same charge from inside the Bible: “the lying pen of the scribes has made it into a lie.”")))),
     2)

# ---------------------------------------------------------------- Resources
RES = [
    ("Qur’an texts", [
        ("Tanzil", "https://tanzil.net", "Verified Arabic text of the Qur’an in several scripts, with translations for comparison."),
        ("Quran.com", "https://quran.com", "Arabic with word-by-word glosses and multiple English translations."),
        ("Corpus Coranicum", "https://corpuscoranicum.de", "Academic project documenting early Qur’an manuscripts and textual variants.")]),
    ("Classical commentary", [
        ("al-Ṭabarī, <em>Jāmiʿ al-Bayān</em>", None, "The earliest large commentary, preserving reports from the Companions on verses such as 5:48 and 10:94."),
        ("Ibn Taymiyyah, <em>al-Jawāb al-Ṣaḥīḥ</em>", None, "A fourteenth-century reply to Christian apologetics. Partial English translation by Thomas F. Michel (1984)."),
        ("Altafsir.com", "https://www.altafsir.com", "Searchable Arabic texts of the major classical commentaries.")]),
    ("The Bible and its history", [
        ("Codex Sinaiticus", "https://codexsinaiticus.org", "Full images and transcription of the fourth-century Greek Bible manuscript."),
        ("ESV Bible", "https://www.esv.org", "The translation quoted on this site. Its own footnotes mark passages missing from the earliest manuscripts."),
        ("Bruce M. Metzger, <em>A Textual Commentary on the Greek New Testament</em>", None, "Standard reference on New Testament textual variants, by a Christian scholar.")]),
]
res_blocks = [P("Everything this site cites, so you can check the claims yourself. Where a source is online, it is linked.", "is-style-lead")]
for heading, items in RES:
    res_blocks.append(H(2, heading))
    res_blocks.append(LIST([(f'<a href="{u}">{n}</a>' if u else n) + f" — {t}" for n, u, t in items], c="res-list"))
res_blocks += [H(2, "Photograph credits"),
               LIST(['Qur’an on a dark background: <a href="https://www.pexels.com/photo/close-up-of-the-quran-on-a-black-background-12039705/">Osman Özavcı / Pexels</a>',
                     'Illuminated manuscript, Konya: <a href="https://www.pexels.com/photo/a-close-up-of-an-old-quran-book-27980497/">astar / Pexels</a>',
                     'Arabic calligraphy: <a href="https://www.pexels.com/photo/close-up-of-a-prayer-in-the-quran-12039709/">Osman Özavcı / Pexels</a>',
                     'Torah scroll and yad: <a href="https://www.pexels.com/photo/silver-and-gold-pen-on-white-paper-5986498/">cottonbro studio / Pexels</a>'], c="res-list")]
page("resources", "Sources and scholarship", "Everything this site cites, so you can check the claims yourself.",
     article(res_blocks, sidebar(side_box("Using these sources", P("Quote verses with their surah and verse number, in Arabic where possible, and check any English rendering against the original.", "gloss")))), 5)

# ---------------------------------------------------------------- About
page("about", "About this site", "Who we are, how we work, and how to correct us.",
     article([
         H(2, "What this site is", anchor="what"),
         P("islamicdilemma.info is a Muslim reply to the argument known as the “Islamic Dilemma”, and an invitation to Islam."),
         H(2, "Editorial standards", anchor="standards"),
         LIST(["Every verse is quoted in Arabic with its reference before any translation.",
               "Verses are read with the passage around them, not in isolation.",
               "The opposing argument is stated as its advocates make it, before we answer it.",
               "We criticise ideas and arguments, never people. Christians are addressed as the People of the Book, with respect.",
               "Sources are cited by work and section so readers can check them."]),
         H(2, "Corrections", anchor="corrections"),
         P("If we have misquoted a source or misstated the argument, tell us and we will correct it and note the change at the end of the page."),
         G("is-style-callout", P("Contact address to be added before launch.")),
     ], sidebar()), 6)

# ---------------------------------------------------------------- Auxiliary pages
page("why-islam", "Why Islam? The message in one page",
     "One God, His mercy, accountability, and the prophets He sent, from Abraham to Jesus to Muhammad.",
     article([
         P("Islam’s message is short enough to fit on one page. It is the message the Qur’an says every prophet brought: worship God alone, "
           "and live as someone who will meet Him.", "is-style-lead"),
         H(2, "One God", anchor="one-god"),
         AYAH(Q["112"], "Say: He is God, the One. God, the Eternal Refuge. He neither begets nor is begotten, and there is none comparable to Him.", "Q 112:1–4"),
         P("God is one in His being, His names and His right to be worshipped. He has no partner, no son and no equal. Nothing in creation shares in "
           "His divinity, however honoured it is. This is <em>tawḥīd</em>, and it is the first thing the Qur’an asks of every person."),
         H(2, "Near, and merciful", anchor="near"),
         AYAH(Q["2:186"], "When My servants ask you about Me, I am near. I answer the call of the one who calls, when he calls on Me.", "Q 2:186"),
         P(f"Islam has no priesthood standing between a person and God, and no ritual is needed to be heard. God is “nearer to him than his jugular vein” "
           f"{R('Q 50:16')}, and among His names are <em>al-Wadūd</em>, the Loving {R('Q 85:14')}, and <em>al-Raḥīm</em>, the Merciful, which opens "
           "every surah but one."),
         H(2, "Accountable, each for our own", anchor="accountable"),
         P(f"Every person will answer for what they did, and “no bearer of burdens bears the burden of another” {R('Q 6:164')}. No one is born guilty "
           f"of another’s sin, and no one is asked for more than they can bear {R('Q 2:286')}. What is asked is faith, prayer, honesty, charity and "
           "turning back to God when we fall short. <a href=\"/sin-and-repentance/\">Sin and repentance</a> explains how forgiveness works."),
         H(2, "The prophets, one message", anchor="prophets"),
         P(f"Muslims believe in Abraham, Moses, David and Jesus, and honour them. The Qur’an calls Jesus the Messiah, born of the virgin Mary, a word "
           f"from God and a spirit from Him, who healed the blind and the leper by God’s permission {R('Q 3:45–49')}. His message, in the Qur’an and in "
           f"the Gospels alike, was to worship “God, my Lord and your Lord” {R('Q 5:72')} {R('John 20:17')}. Muhammad was sent with the same message, "
           "the last of the prophets, with a book God promised to guard."),
         H(2, "Becoming Muslim", anchor="becoming"),
         P("Becoming Muslim is simple. It needs no priest and no ceremony: a person who believes it bears witness, sincerely, in their own words or "
           "these:"),
         G("is-style-callout",
           P("أَشْهَدُ أَنْ لَا إِلَٰهَ إِلَّا اللَّهُ وَأَشْهَدُ أَنَّ مُحَمَّدًا رَسُولُ اللَّهِ", "is-style-arabic"),
           P("“I bear witness that there is no god but God, and I bear witness that Muhammad is the messenger of God.”")),
         P(f"There is “no compulsion in religion” {R('Q 2:256')}. If you are thinking about it, take your time, read the Qur’an for yourself, and "
           "speak to Muslims you trust. If you have questions, the <a href=\"/about/\">About</a> page explains how to reach us."),
     ], sidebar(side_box("Read next",
                         P('<a href="/sin-and-repentance/">Sin and repentance in Islam</a>', "gloss"),
                         P('<a href="/invitation-to-christians/">An invitation to Christians</a>', "gloss"),
                         P('<a href="/paul-and-message-of-jesus/">Paul and the message of Jesus</a>', "gloss")))), 7)

page("sin-and-repentance", "Sin and repentance in Islam",
     "How Islam understands sin, forgiveness and mercy, and why “Muslims forgive their own sins” misstates it.",
     article([
         P("A common claim is that Islam teaches people to forgive their own sins, or that good deeds simply cancel bad ones. Islam teaches neither. "
           "Forgiveness comes from God alone, and it is given out of His mercy.", "is-style-lead"),
         H(2, "Only God forgives", anchor="only-god"),
         AYAH(Q["39:53"], "Say: My servants who have wronged yourselves, do not despair of God’s mercy. God forgives all sins.", "Q 39:53"),
         P(f"The Qur’an asks “who forgives sins except God?” {R('Q 3:135')}. A Muslim who sins turns to God, regrets it, stops, and resolves not to "
           "return to it. That is <em>tawbah</em>, repentance, and the word means turning back. No one can forgive themselves; they can only ask."),
         H(2, "Saved by mercy, not by a ledger", anchor="mercy"),
         P("The Prophet said that no one enters Paradise by their deeds alone. When his companions asked, “Not even you?”, he answered, “Not even me, "
           "unless God covers me with His mercy.”<sup class=\"n\"><a href=\"#s1\" id=\"sr1\">1</a></sup> Good deeds matter, and the Qur’an says they "
           f"wipe away bad ones {R('Q 11:114')}, but that is God’s promise and God’s gift, not a balance a person settles."),
         P("He also said that God is more pleased with the repentance of His servant than a traveller who loses his camel, with all his food and "
           "water, in the desert, and then finds it again.<sup class=\"n\"><a href=\"#s2\" id=\"sr2\">2</a></sup>"),
         H(2, "No inherited guilt, no one punished for another", anchor="no-inherited-guilt"),
         P(f"Adam and Eve sinned, repented and were forgiven {R('Q 2:37')}. Their children do not inherit their guilt. “No bearer of burdens bears the "
           f"burden of another” {R('Q 6:164')}, so God does not need to punish an innocent person in order to forgive a guilty one."),
         P("The Hebrew Bible says the same:"),
         QUOTE("“But if a wicked person turns away from all his sins that he has committed and keeps all my statutes and does what is just and right, "
               "he shall surely live; he shall not die. None of the transgressions that he has committed shall be remembered against him.”",
               "Ezekiel 18:21–22 · ESV"),
         H(2, "Are all sins equal?", anchor="degrees"),
         P("Some argue that a small lie and a murder are the same in God’s sight. Islam distinguishes major sins from minor ones, and so does the Gospel "
           "of John, where Jesus tells Pilate that “he who delivered me over to you has the greater sin” (John 19:11). Every sin is serious; "
           "they are not all the same, and God judges with perfect justice and perfect mercy."),
         *notes(["<em>Ṣaḥīḥ al-Bukhārī</em> and <em>Ṣaḥīḥ Muslim</em>, from Abū Hurayrah and ʿĀʾishah. <a href=\"#sr1\">↩</a>",
                 "<em>Ṣaḥīḥ al-Bukhārī</em> and <em>Ṣaḥīḥ Muslim</em>, from Anas ibn Mālik. <a href=\"#sr2\">↩</a>"]),
     ], sidebar(side_box("Read next", P('<a href="/why-islam/">Why Islam? The message in one page</a>', "gloss"),
                         P('<a href="/invitation-to-christians/">An invitation to Christians</a>', "gloss")))), 8)

page("invitation-to-christians", "An invitation to Christians",
     "Turn back to the worship of the One God, the God Jesus worshipped. A call to repentance from shirk.",
     article([
         P("This page is addressed to Christians, as the Qur’an addresses them: as People of the Book, who already know of Abraham, Moses and Jesus. "
           "It is an invitation, and it is meant seriously.", "is-style-lead"),
         H(2, "A common word", anchor="common-word"),
         AYAH(Q["3:64"], "Say: People of the Book, come to a word common to us and you: that we worship none but God, and associate nothing with Him.", "Q 3:64"),
         P(f"Christians and Muslims agree that there is one God. The disagreement is over whether anything else shares in His divinity. The Qur’an "
           f"calls that sharing <em>shirk</em>, associating partners with God, and calls it the one sin God does not forgive if a person dies upon it "
           f"{R('Q 4:48')}. Every other sin, it says, God may forgive."),
         H(2, "Why the Trinity is shirk", anchor="shirk"),
         P("From the Qur’an’s point of view, worshipping Jesus or the Holy Spirit alongside God gives a created being what belongs to God alone. "
           "Christians understand the Trinity as one God in three persons, and they do not see it as worshipping three gods. The Qur’an addresses "
           "the doctrine directly all the same:"),
         AYAH(Q["5:72"], "And the Messiah said: Children of Israel, worship God, my Lord and your Lord.", "Q 5:72"),
         P(f"It goes on to tell those who say God is “the third of three” to stop {R('Q 5:73')}, and then asks a question that is also an invitation:"),
         AYAH(Q["5:74"], "Will they not turn back to God and ask His forgiveness? God is Forgiving, Merciful.", "Q 5:74"),
         H(2, "What Jesus said about God", anchor="jesus-on-god"),
         P("The Gospels preserve sayings in which Jesus speaks as a servant of the One God:"),
         QUOTE("“The most important is, ‘Hear, O Israel: The Lord our God, the Lord is one.’”", "Mark 12:29 · ESV"),
         QUOTE("“And this is eternal life, that they know you, the only true God, and Jesus Christ whom you have sent.”", "John 17:3 · ESV"),
         QUOTE("“I am ascending to my Father and your Father, to my God and your God.”", "John 20:17 · ESV"),
         P("“The only true God,” and Jesus as the one sent. “My God and your God.” That is the Qur’an’s account of Jesus in his own words."),
         H(2, "Repent, and come home", anchor="repent"),
         P(f"Repentance in Islam is turning back to God directly. It needs no intermediary and no sacrifice. Whoever turns from shirk to "
           f"<em>tawḥīd</em> is forgiven what came before {R('Q 8:38')}. Becoming Muslim does not mean leaving Jesus behind. Muslims honour him "
           "as the Messiah and a prophet, and follow the message he preached: worship God alone."),
         P("If you are ready, <a href=\"/why-islam/#becoming\">Why Islam?</a> explains how to become Muslim. If you are not, read on, ask your "
           "questions, and take your time. There is “no compulsion in religion” " + R("Q 2:256") + "."),
     ], sidebar(side_box("Read next", P('<a href="/paul-and-message-of-jesus/">Paul and the message of Jesus</a>', "gloss"),
                         P('<a href="/why-islam/">Why Islam? The message in one page</a>', "gloss")))), 9)

page("paul-and-message-of-jesus", "Paul and the message of Jesus",
     "Paul never followed Jesus in his lifetime, says his gospel came by private revelation, and taught what James contradicts.",
     article([
         P("Much of what Christians believe about salvation comes not from the Gospels but from the letters of Paul. Muslims have long argued that "
           "Paul’s teaching departs from the message of Jesus. The evidence for that comes from the New Testament itself.", "is-style-lead"),
         H(2, "Paul never followed Jesus in his lifetime", anchor="never-met"),
         P("Paul did not walk with Jesus during his ministry. He persecuted Jesus’ followers, then reported a vision. He says plainly that his gospel "
           "did not come from the disciples:"),
         QUOTE("“For I would have you know, brothers, that the gospel that was preached by me is not man’s gospel. For I did not receive it from any man, "
               "nor was I taught it, but I received it through a revelation of Jesus Christ.”", "Galatians 1:11–12 · ESV"),
         P("A gospel received privately, by someone who did not hear Jesus teach, is exactly the kind of source the Qur’an’s word <em>muhaymin</em> "
           "asks us to test."),
         H(2, "Paul against the disciples", anchor="against-peter"),
         QUOTE("“But when Cephas came to Antioch, I opposed him to his face, because he stood condemned.”", "Galatians 2:11 · ESV"),
         P("Cephas is Peter, whom the Gospels present as the leading disciple. Paul’s own letter records that he publicly condemned him."),
         H(2, "Faith alone, or works?", anchor="faith-works"),
         P("Paul and James, the brother of Jesus and leader of the Jerusalem church, answer the same question in opposite words:"),
         QUOTE("“For we hold that one is justified by faith apart from works of the law.”", "Romans 3:28 · ESV"),
         QUOTE("“You see that a person is justified by works and not by faith alone.”", "James 2:24 · ESV"),
         P("Jesus, asked how to gain eternal life, answered: “If you would enter life, keep the commandments” (Matthew 19:17). He said he had not come "
           "to abolish the Law, and that not a dot of it would pass away (Matthew 5:17–18)."),
         H(2, "What follows", anchor="what-follows"),
         P("None of this requires disrespect for Christians, who sincerely follow what they were taught. It does mean the question “what did Jesus "
           "teach?” cannot be settled by Paul’s letters. The Qur’an’s answer is that Jesus taught what every prophet taught: worship the One God and "
           "obey Him. That is the invitation on <a href=\"/invitation-to-christians/\">An invitation to Christians</a>."),
     ], sidebar(side_box("Margin notes",
                         gloss("Sources", "All Bible quotations are from the ESV, a Protestant translation."),
                         gloss("Note", "Christian scholars reconcile Paul and James in several ways. This page sets out the texts; readers can weigh the reconciliations themselves.")))), 10)
