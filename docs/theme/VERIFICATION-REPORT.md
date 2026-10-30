# Verification report (theme 1.1.0)

This report records what was checked against primary sources, what changed as a result, and what still needs a qualified scholar. It supersedes the "must confirm" list in `VERIFICATION-CHECKLIST.md`.

## Qur'an

- **Arabic text:** all 45 Arabic quotations were compared word by word with the Uthmani/imlaei text served by Quran.com. All are identical, including vowelling.
- **References:** all 235 verse references in the content were checked for valid surah and verse numbers, and each was read for whether it says what the article claims.
- **Four wording fixes:**
  - Q 5:41 now says "some among the Jews of Medina".
  - Q 6:91 says "in Sūrat al-Anʿām" instead of "a Meccan surah".
  - Q 3:4 says it calls the Torah and the Gospel "guidance for people".
  - The Q 20:109 wording on intercession was made precise.

## Hadith (Sunnah.com numbering)

Each hadith was matched by its Arabic text in the standard editions. For Muslim, the numbering is ʿAbd al-Bāqī's, which is what Sunnah.com uses.

| Hadith | Reference | Used in |
|---|---|---|
| "None of you will enter Paradise by his deeds … not even me" | bukhari:6463, muslim:2816 (Abū Hurayrah) | Sin and repentance; Is the God of Islam distant?; Errors page |
| God's joy at repentance and the traveller's lost camel | bukhari:6309, muslim:2747 (Anas) | Sin and repentance |
| "I am as My servant thinks of Me … I come to him running" (qudsī) | bukhari:7405, muslim:2675 (Abū Hurayrah) | Is the God of Islam distant? |
| "My mercy prevails over My wrath" | bukhari:7422, muslim:2751 (Abū Hurayrah) | Is the God of Islam distant? |
| "Do not believe the People of the Book and do not disbelieve them" | bukhari:4485 (Abū Hurayrah) | Q 29:46 post |
| Ḥadīth of Jibrīl (belief in the books) | muslim:8 | Muslim Response |
| The Torah, the hand over the stoning verse, Ibn Salām | bukhari:3635, muslim:1699 (Ibn ʿUmar) | Judge by it, uphold it |

All of these numbers are now in the article footnotes.

## Tafsīr (al-Ṭabarī and Ibn Kathīr, read in Arabic)

| Claim | Result | Change |
|---|---|---|
| Qatādah: "I do not doubt and I do not ask" (Q 10:94) | Confirmed: al-Ṭabarī reports 17893–17894. ʿAbdullāh ibn Salām is named, from Ibn Zayd | Caveat removed; the note gives the report numbers and says the report is *mursal* |
| Ibn ʿAbbās on *muhaymin* (Q 5:48) | al-Ṭabarī reports *shahīd*, *muʾtaman* and *amīn*; Ibn Kathīr adds *ḥākim* | Glosses corrected on the Muslim Response page and in the 5:48 post |
| Ibn Kathīr on 5:48 | The 5:48 post paraphrased him | Replaced with the exact Ibn Jurayj quotation that both commentators give |
| al-Ṭabarī on 6:115 | "No one can change what God has said will come to pass"; he cites 48:15 | Wording aligned |
| al-Ṭabarī on 5:66 and Ibn Kathīr on 5:68 | "What was sent down to them" means the Furqān / the Qur'an (Mujāhid) | Note made specific |
| Reading *wa-li-yaḥkuma* (Q 5:47) | al-Ṭabarī attributes it to "a group of the people of Kufa" and does not name Ḥamzah in this passage | Now "a group of the readers of Kufa", without naming Ḥamzah |

## Confirmed on al-Maktabah al-Shāmilah (1.1.0)

- **The *yuḥarrifūna* remark** is in al-Bukhārī's chapter heading immediately before hadith 7553 (Kitāb al-Tawḥīd, Sulṭāniyyah edition, vol. 9). It follows a remark from Ibn ʿAbbās, so the note now says the commentators discuss whose words it is.

## Bible quotations (1.1.0)

- Every ESV quotation on the site was checked word for word against the ESV text: 39 block quotations and 62 inline. All match.
- Two quotations in the blindness article are the opponent's own NIV wording. They are labelled NIV.
- One inline quotation (Matthew 11:22) was corrected to the exact ESV wording.
- The checker is in `tools/verify/esv.py` and `tools/verify/esv_inline.py`.

## New articles (1.1.0): sources checked

| Article | Checked |
|---|---|
| The qirāʾāt and the preservation of the Qur'an | Seven *aḥruf* (bukhari:4992, muslim:818); Abū Bakr's collection (bukhari:4986); ʿUthmān's copies (bukhari:4987); the Uthmani spelling of Q 1:4, 43:19, 3:146 and 2:140 from the Qur'an text; al-Imām, *Variant Readings of the Qur'an*, introduction xi–xii, chapter 4 pp. 50–57, conclusion 112–113 |
| Does 15:9 depend on 6:115? | al-Ṭabarī on 15:9 (Mujāhid and Qatādah) and on 6:115; Q 3:45, 3:59, 10:19, 10:33, 40:6, 41:42, 75:17 |
| Did Jesus claim to be God? | All ESV quotations; Q 19:30, 5:117 and 5:75; al-Ṭabarī's two views on when the question in 5:116 is asked |
| "He does not beget" and 5:116 | Q 19:88, 19:92, 2:116, 6:101, 9:30, 5:72–73 and 43:59; al-Ṭabarī reports 13028–13029 |
| Q 4:157 and the crucifixion | al-Ṭabarī on 4:157 (reports 10781–10782 and 10789, and his preference for Wahb ibn Munabbih's report); al-Ṭabarī on 3:55; bukhari:3448 and muslim:155 |
| What the manuscripts can and cannot show | ESV quotations from Galatians and 2 Corinthians |

Classical and secondary sources cited from standard knowledge, which a scholar should confirm against print:
- Tacitus, *Annals* 15.44;
- Josephus, *Antiquities* 18.63–64;
- Irenaeus, *Against Heresies* 1.24.4 and 1.26.2;
- Theodoret, *Compendium of Heretical Fables* 1.20;
- Athanasius, *Festal Letter* 39;
- Nongbri (2005);
- Sadeghi and Goudarzi (2012);
- the Birmingham and Tübingen radiocarbon dates.

## Still for a qualified scholar

1. **Ibn Taymiyyah's position in *al-Jawāb al-Ṣaḥīḥ*:** that both wording and meaning were altered in places, while much of the text remained. This is well documented in Michel's translation but was not read in the Arabic here.
2. **ʿAbd al-Jabbār's *Tathbīt*:** its characterisation now cites the Reynolds and Samir translation (2010). It was not read in the original.
3. **The Kufan reading of Q 5:47:** if a work on the seven readings confirms that it is Ḥamzah's, the name can be added back.
