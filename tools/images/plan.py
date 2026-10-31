"""Which photographs each article gets: one featured image (where it has none) and three more.

Each slot is (source, search, subject). `subject` is both the alt text and the opening of the caption, so
it says what the picture shows, plainly. The first suitable result is taken (licence accepted, at least
1000 px wide, landscape preferred, not used elsewhere); check the contact sheet and refine a search if a
pick does not match its subject.
"""
C, P, X = "commons", "pexels", "pixabay"

PLAN = {
 "10-94": [
  (C, "Quran folio Kufic script parchment", "A folio of an early Qur'an manuscript in Kufic script"),
  (P, "torah scroll hebrew text", "Hebrew text in a Torah scroll"),
  (C, "Codex Alexandrinus Greek Bible manuscript", "A page of Codex Alexandrinus, a fifth-century Greek Bible"),
  (C, "Prophet's Mosque Medina", "The Prophet's Mosque in Medina"),
 ],
 "15-9": [
  (C, "Topkapi Uthman Quran manuscript", "A Qur'an manuscript in the Topkapı Palace collection"),
  (C, "Sanaa palimpsest Quran", "A leaf of the Sanaa palimpsest, an early Qur'an manuscript"),
  (P, "reading quran mushaf", "A reader holding a printed Qur'an"),
  (P, "mosque interior prayer hall light", "The prayer hall of a mosque"),
 ],
 "29-46": [
  (C, "Hagia Sophia interior", "The interior of Hagia Sophia, Istanbul, a church for nearly a thousand years and later a mosque"),
  (P, "old library bookshelves", "Shelves of old books in a library"),
  (C, "Mosque-Cathedral of Cordoba arches", "The arches of the Mosque-Cathedral of Córdoba"),
 ],
 "4-157": [
  (C, "Alexamenos graffito", "The Alexamenos graffito, a Roman carving of a crucified figure from about the third century"),
  (C, "Pilate Stone Caesarea inscription", "The Pilate Stone, a first-century inscription naming Pontius Pilate"),
  (C, "Tacitus Annals Second Medicean manuscript", "A page from the Second Medicean manuscript of Tacitus"),
  (C, "Church of the Holy Sepulchre Jerusalem", "The Church of the Holy Sepulchre, Jerusalem"),
 ],
 "5-48": [
  (C, "Quran manuscript gold illuminated page", "An illuminated page of a Qur'an manuscript"),
  (C, "Aleppo Codex page", "A page of the Aleppo Codex, a tenth-century Hebrew Bible"),
  (C, "Codex Sinaiticus leaf", "A leaf of Codex Sinaiticus, a fourth-century Greek Bible"),
  (C, "Great Isaiah Scroll Dead Sea", "The Great Isaiah Scroll from the Dead Sea Scrolls"),
 ],
 "alteration": [
  (C, "Samaritan Pentateuch scroll", "A Samaritan Pentateuch scroll"),
  (C, "Dead Sea Scrolls Qumran fragment", "A fragment of the Dead Sea Scrolls"),
  (P, "quill ink parchment writing", "A quill and ink on parchment"),
 ],
 "blindness": [
  (P, "sunlight window dark room", "Sunlight entering a dim room through a window"),
  (P, "candle flame darkness", "A candle burning in the dark"),
  (P, "open door light path", "An open door with light beyond it"),
 ],
 "distant-god": [
  (P, "milky way night sky stars", "The Milky Way over a dark landscape"),
  (P, "desert dunes sunrise", "Desert dunes at sunrise"),
  (P, "mosque interior prayer carpet", "The carpeted prayer hall of a mosque"),
  (P, "minaret dawn sky", "A minaret against the dawn sky"),
 ],
 "every-verse": [
  (C, "illuminated gospel book manuscript", "An illuminated Gospel book"),
  (C, "Peshitta Syriac Bible manuscript", "A page of a Syriac Peshitta manuscript"),
  (C, "Garima Gospels Ethiopian", "A page of the Garima Gospels, an Ethiopian Gospel book"),
  (C, "Quran manuscript Maryam", "A page of a Qur'an manuscript"),
 ],
 "hebrew-bible-text": [
  (C, "Great Isaiah Scroll", "The Great Isaiah Scroll"),
  (C, "Leningrad Codex page", "A page of the Leningrad Codex, the oldest complete Masoretic Hebrew Bible"),
  (C, "Samaritan Pentateuch Nablus", "A Samaritan Pentateuch, Nablus"),
  (C, "Qumran Cave 4 Dead Sea Scrolls caves", "The caves at Qumran where the Dead Sea Scrolls were found"),
 ],
 "human-book": [
  (C, "Quran calligraphy manuscript page", "A page of a Qur'an manuscript in classical calligraphy"),
  (C, "Blue Quran Kairouan", "A folio of the Blue Qur'an, Kairouan"),
  (P, "arabic calligraphy ink", "Arabic calligraphy in ink"),
  (P, "scholar reading old book", "A scholar reading an old book"),
 ],
 "injil": [
  (C, "Rabbula Gospels Syriac", "A page of the Rabbula Gospels, a sixth-century Syriac manuscript"),
  (C, "Diatessaron Dura-Europos fragment", "The Dura-Europos fragment of Tatian's Diatessaron"),
  (C, "Papyrus 52 Rylands", "Papyrus 52, a fragment of the Gospel of John from about the second century"),
 ],
 "jesus-claims": [
  (C, "Sea of Galilee", "The Sea of Galilee"),
  (C, "Papyrus 66 Gospel of John", "Papyrus 66, an early manuscript of the Gospel of John"),
  (C, "Temple Mount Jerusalem Western Wall", "The Temple Mount and the Western Wall, Jerusalem"),
  (C, "Codex Bezae John", "A page of Codex Bezae"),
 ],
 "judge-uphold": [
  (C, "Quran manuscript ornate page Mamluk", "An ornate page of a Mamluk Qur'an"),
  (P, "torah scroll yad", "A Torah scroll and a reader's pointer"),
  (C, "Gospel of Matthew Greek manuscript", "A Greek manuscript of the Gospel of Matthew"),
  (C, "Quba Mosque Medina", "Quba Mosque, Medina"),
 ],
 "letters-deity": [
  (C, "Papyrus 46 Pauline epistles", "Papyrus 46, an early manuscript of the letters of Paul"),
  (C, "Codex Claromontanus", "A page of Codex Claromontanus, a Greek and Latin manuscript of Paul's letters"),
  (C, "Roman Forum ruins", "The Roman Forum"),
  (C, "Ephesus Library of Celsus", "The Library of Celsus, Ephesus"),
 ],
 "manuscripts": [
  (C, "Codex Bezae Greek Latin", "Codex Bezae, a Greek and Latin manuscript of the Gospels and Acts"),
  (C, "New Testament papyrus fragment", "A New Testament papyrus fragment"),
  (C, "Codex Washingtonianus", "A page of the Washington Codex of the Gospels"),
  (C, "Codex Vaticanus page", "A page of Codex Vaticanus"),
 ],
 "no-distinction": [
  (P, "quran bible torah books", "The Torah, the Gospel and the Qur'an as books"),
  (C, "Quran folio Abbasid Kufic", "A folio of an Abbasid Qur'an in Kufic script"),
  (C, "Codex Washingtonianus", "A Greek manuscript of the Gospels"),
  (P, "torah scroll hebrew", "A Torah scroll"),
 ],
 "none-can-change": [
  (C, "Samarkand Kufic Quran", "A leaf of the Samarkand Kufic Qur'an"),
  (C, "Tashkent Quran Uthman", "A page of the Tashkent Qur'an"),
  (P, "pen ink manuscript writing", "A pen resting on a handwritten manuscript"),
 ],
 "polemical-setting": [
  (C, "Medina Masjid al-Nabawi", "Medina and the Prophet's Mosque"),
  (C, "Hegra Mada'in Salih Arabia", "Rock tombs at Hegra, Arabia, in the Prophet's region"),
  (C, "Khaybar oasis", "The Khaybar oasis, Arabia"),
  (C, "Arabian desert caravan", "A caravan crossing the Arabian desert"),
 ],
 "proof-texts": [
  (C, "Codex Montfortianus Comma Johanneum", "Codex Montfortianus, the manuscript that gave Erasmus the Comma Johanneum"),
  (C, "Erasmus Novum Instrumentum 1516", "The title page of Erasmus's Greek New Testament, 1516"),
  (C, "Textus Receptus Stephanus Greek New Testament", "A printed Greek New Testament of the sixteenth century"),
  (C, "Codex Sinaiticus Matthew", "A page of Codex Sinaiticus"),
 ],
 "qiraat": [
  (C, "Quran manuscript red dots vowels", "A Qur'an manuscript with coloured dots marking vowels"),
  (C, "Quran Ottoman Hafs mushaf page", "A page of an Ottoman Qur'an"),
  (C, "Samarkand Kufic Quran leaf", "A leaf of the Samarkand Kufic Qur'an"),
  (P, "quran reading mushaf stand", "A Qur'an on a reading stand"),
 ],
 "son-of-god": [
  (C, "Council of Nicaea icon", "An icon of the First Council of Nicaea with the Nicene Creed"),
  (C, "Nicene Creed Greek manuscript", "A manuscript of the Nicene Creed"),
  (P, "church interior cross", "The interior of a church"),
  (P, "mosque ceiling geometric", "The geometric ceiling of a mosque"),
 ],
 "suhuf": [
  (C, "Rembrandt Moses Tablets of the Law", "Moses with the tablets of the Law, painted by Rembrandt in 1659"),
  (C, "Dead Sea Scrolls Psalms scroll", "The Psalms Scroll from the Dead Sea Scrolls"),
  (C, "Temple Scroll Dead Sea", "The Temple Scroll from the Dead Sea Scrolls"),
  (C, "papyrus scroll ancient Egypt", "An ancient papyrus scroll"),
 ],
 "tahrif-scholarship": [
  (C, "Tafsir al-Tabari manuscript", "A manuscript of al-Ṭabarī's Qur'an commentary"),
  (C, "Fakhr al-Din al-Razi Tafsir manuscript", "A manuscript of al-Rāzī's Qur'an commentary"),
  (C, "Arabic manuscript library Timbuktu", "Arabic manuscripts preserved in Timbuktu"),
  (C, "Al-Azhar Mosque library", "Al-Azhar, Cairo, a centre of Islamic learning for a thousand years"),
 ],
 "three-contrasts": [
  (C, "Rublev Trinity icon", "Andrei Rublev's icon of the Holy Trinity"),
  (C, "Shield of the Trinity Scutum Fidei", "The Shield of the Trinity"),
  (C, "Surat al-Ikhlas manuscript", "A manuscript page of Sūrat al-Ikhlāṣ"),
  (P, "arabic calligraphy gold", "Arabic calligraphy in gold"),
 ],
 "two-standards": [
  (P, "scales of justice balance", "A set of balance scales"),
  (C, "Bodleian Library Duke Humfrey's Library", "Duke Humfrey's Library, Oxford"),
  (P, "old books library shelves", "Old books on a library shelf"),
  (C, "Qumran Cave 4", "Qumran Cave 4, where the Dead Sea Scrolls were found"),
 ],
 "which-bible": [
  (C, "Codex Alexandrinus page", "A page of Codex Alexandrinus"),
  (C, "Muratorian fragment", "The Muratorian fragment, an early list of New Testament books"),
  (C, "Ethiopian Bible Ge'ez manuscript", "A Ge'ez manuscript of the Ethiopian Bible"),
  (C, "Gutenberg Bible", "A page of the Gutenberg Bible"),
 ],
}

# Second pass, after checking the contact sheets: empty slots, a duplicate scroll, an image with text laid over it,
# and captions that claimed more than the picture showed.
PLAN["4-157"][0] = (C, "Yehohanan crucified man heel bone nail Jerusalem", "The heel bone of a man crucified near Jerusalem in the first century, with the iron nail still through it")
PLAN["4-157"][2] = (C, "Tacitus Annals Medicean manuscript Florence", "A page of the Second Medicean manuscript of Tacitus, which records the execution of Christ under Pontius Pilate")
PLAN["5-48"][2] = (C, "Codex Sinaiticus British Library manuscript", "A leaf of Codex Sinaiticus, a fourth-century Greek Bible")
PLAN["alteration"][1] = (C, "Dead Sea Scrolls fragment Qumran Hebrew parchment photograph", "A fragment of a Dead Sea Scroll")
PLAN["every-verse"][0] = (C, "evangelist portrait medieval Gospel book manuscript", "An evangelist portrait from a medieval Gospel book")
PLAN["every-verse"][1] = (C, "Syriac Bible manuscript Estrangela", "A page of an early Syriac Bible manuscript")
PLAN["hebrew-bible-text"][0] = (C, "Shrine of the Book Jerusalem", "The Shrine of the Book in Jerusalem, where the Great Isaiah Scroll is kept")
PLAN["hebrew-bible-text"][3] = (C, "Qumran caves cliffs Dead Sea", "The cliffs and caves at Qumran, near the Dead Sea")
PLAN["human-book"][0] = (C, "Quran Ibn al-Bawwab Chester Beatty manuscript", "A page of the Qur'an copied by Ibn al-Bawwab in Baghdad in 1000-1001")
PLAN["human-book"][3] = (C, "man reading manuscript library historic", "A man reading in a library")
PLAN["injil"][1] = (C, "Dura-Europos Gospel harmony fragment Yale", "The Dura-Europos fragment of a Greek Gospel harmony, probably Tatian's Diatessaron")
PLAN["injil"][2] = (C, "Papyrus 52 recto Rylands Greek 457", "Papyrus 52, a fragment of the Gospel of John from about the second century")
PLAN["jesus-claims"][3] = (C, "Codex Bezae Cantabrigiensis", "A page of Codex Bezae, a Greek and Latin manuscript of the Gospels and Acts")
PLAN["judge-uphold"][0] = (C, "Quran Mamluk illuminated frontispiece", "An illuminated page of a Mamluk Qur'an")
PLAN["10-94"][3] = (C, "Prophet's Mosque Medina", "A plan of the original Prophet's Mosque in Medina")
PLAN["15-9"][0] = (C, "Topkapi Uthman Quran manuscript", "An illuminated page of a Qur'an manuscript")
PLAN["distant-god"][1] = (P, "desert dunes sunrise", "Sand dunes in the desert")
PLAN["distant-god"][3] = (P, "minaret dawn sky", "A minaret silhouetted against a red sky")

