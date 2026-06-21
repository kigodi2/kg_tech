<?php

namespace App\Services\Results;

class RegionalResultBookNarrativeService
{
    public function getExecutiveSummary(array $data): string
    {
        $meta = $data['meta'];
        $profile = $data['region_profile'];
        $att = $data['attendance'];
        $perf = $data['performance'];

        $totalSchools = number_format($profile['total_schools']);
        $registered = number_format($att['registered_total']);
        $satTotal = number_format($att['sat_total']);
        $attendanceRate = number_format($att['attendance_rate'], 2);

        $passAC = $perf['regional']['pass'] ?? 0;
        $satVal = $perf['regional']['sat'] ?? 0;
        $passRate = $satVal > 0 ? number_format(($passAC / $satVal) * 100, 2) : '0.00';

        $topCouncil = isset($perf['councils'][0]) ? $perf['councils'][0]['name'] : 'N/A';
        $topCouncilAvg = isset($perf['councils'][0]) ? number_format($perf['councils'][0]['average_marks'], 2) : '0.00';

        $topSchool = isset($perf['top_schools'][0]) ? $perf['top_schools'][0]['name'] : 'N/A';
        $topSchoolAvg = isset($perf['top_schools'][0]) ? number_format($perf['top_schools'][0]['average_marks'], 2) : '0.00';

        return "Ripoti hii inatoa muhtasari wa matokeo ya mtihani wa utamilifu wa Darasa la Saba (PSLE Mock) kwa Mwaka " . $meta['exam_year'] . " katika Mkoa wa **" . $meta['region_name'] . "**. " .
            "Mtihani huu ulihusisha jumla ya shule **" . $totalSchools . "** na watahiniwa **" . $registered . "** waliosajiliwa. " .
            "Kati yao, watahiniwa **" . $satTotal . "** walifanya mtihani, ikiwa ni sawa na asilimia **" . $attendanceRate . "%** ya mahudhurio ya jumla. " .
            "Ufaulu wa jumla wa Mkoa (Daraja A-D) umefikia asilimia **" . $passRate . "%** ya watahiniwa wote waliofanya mtihani. " .
            "Halmashauri iliyoongoza kitaaluma katika Mkoa ni **" . $topCouncil . "** ikiwa na wastani wa alama **" . $topCouncilAvg . "**, na shule iliyoongoza kimkoa ni **" . $topSchool . "** yenye wastani wa alama **" . $topSchoolAvg . "**. " .
            "Tathmini ya kina ya takwimu za usajili, mahudhurio, utendaji wa kimasomo, kiutawala na kiumiliki imeainishwa kikamilifu katika kurasa zinazofuata.";
    }

    public function getIntroduction(array $data): string
    {
        $meta = $data['meta'];
        $profile = $data['region_profile'];
        $att = $data['attendance'];

        $councilsText = "";
        foreach ($profile['councils'] as $idx => $c) {
            $councilsText .= ($idx + 1) . ". Halmashauri ya " . $c['name'] . "\n";
        }

        $satPct = $att['attendance_rate'];
        $absPct = $att['registered_total'] > 0 ? round(($att['absent_total'] / $att['registered_total']) * 100, 2) : 0.0;

        return "Mkoa wa **" . $meta['region_name'] . "** una jumla ya shule za Msingi **" . number_format($profile['total_schools']) . "** ambapo shule za Serikali ni **" . number_format($profile['government_schools']) . "** na shule za Binafsi/Zisizo za Serikali ni **" . number_format($profile['private_schools']) . "**. Kiutawala, Mkoa una jumla ya Halmashauri/Wilaya **" . number_format($profile['councils_count']) . "** ambazo ni:\n" .
            $councilsText . "\n" .
            "Jumla ya shule **" . number_format($profile['total_schools']) . "** zenye watahiniwa wa Darasa la Saba wa mwaka **" . $meta['exam_year'] . "** zilifanya Mtihani wa Utamilifu wa Mkoa (Mock).\n\n" .
            "Jumla ya watahiniwa **" . number_format($att['registered_total']) . "** walisajiliwa kufanya mtihani huu, ikijumuisha Wavulana **" . number_format($att['registered_male']) . "** na Wasichana **" . number_format($att['registered_female']) . "**.\n\n" .
            "Kati ya watahiniwa waliosajiliwa, jumla ya watahiniwa **" . number_format($att['sat_total']) . "** walifanya mtihani huo, ikijumuisha Wavulana **" . number_format($att['sat_male']) . "** na Wasichana **" . number_format($att['sat_female']) . "**, ikiwa ni sawa na asilimia **" . number_format($satPct, 2) . "%** ya watahiniwa wote waliosajiliwa. Jumla ya watahiniwa **" . number_format($att['absent_total']) . "** (Wavulana **" . number_format($att['absent_male']) . "**, Wasichana **" . number_format($att['absent_female']) . "**) sawa na asilimia **" . number_format($absPct, 2) . "%** hawakufanya mtihani kutokana na sababu mbalimbali kama vile utoro, ugonjwa na sababu nyingine za kijamii.\n\n" .
            "Mtihani huu wa Utamilifu unafanyika kwa malengo makuu yafuatayo:\n" .
            "* **Kupima Maendeleo ya Kitaaluma:** Kuwapima watahiniwa kitaaluma ili kubaini kiwango cha uelewa wa mada zilizofundishwa tangu kuanza kwa mzunguko wa masomo yao na kuandaa mikakati ya kurekebisha mapungufu kabla ya mtihani wa Taifa.\n" .
            "* **Kupima Utendaji Kazi (KPIs):** Kutathmini utendaji kazi wa walimu (Key Performance Indicators) katika ufundishaji na ujifunzaji na kuweka mikakati ya pamoja kimkoa.\n" .
            "* **Kuzoea Mazingira ya Mitihani:** Kuwajengea watahiniwa ujasiri na uzoefu wa muundo na sheria za mitihani ya Taifa ili kupunguza taharuki wakati wa mtihani wa mwisho.";
    }

    public function getPreparations(array $data): string
    {
        $op = $data['operational'];

        return "Maandalizi ya mtihani yalianza kwa uratibu na vikao vya pamoja vilivyowashirikisha Maafisa Elimu wa Halmashauri (DEOs), Maafisa Taaluma (DTOs), na Wathibiti Ubora wa Shule katika Mkoa. Vikao hivyo vililenga kukubaliana juu ya miongozo ya uendeshaji, usimamizi, usahihishaji, na mifumo ya bajeti.\n\n" .
            "Katika vikao hivyo, makubaliano yafuatayo yalifikiwa:\n" .
            "1. **Uratibu wa Bajeti:** Kikao kiliazimia kuweka na kupitisha bajeti ya jumla ya shilingi **" . number_format((float)$op['budget_amount']) . "** kwa ajili ya uzalishaji wa mitihani, ununuzi wa karatasi na vifaa vya ofisi ikijumuisha matengenezo na uendeshaji wa mashine kubwa za chapa (RISSO) ili kurahisisha zoezi la uzalishaji. Bajeti hii ilichangiwa kutoka kwenye vifungu vya ruzuku ya shule (Capitation Grants) na michango maalum ya uendeshaji ya kila Halmashauri.\n" .
            "2. **Ushirikiano wa Kimkoa/Kikanda (Zonal Collaboration):** Mtihani huu wa utamilifu uliandaliwa kupitia ushirikiano wa Kanda/Mikoa ya **" . $op['collaborating_regions'] . "**, ambapo mikoa iligawana majukumu ya uandaaji wa rasimu za awali za mitihani ya masomo yote kulingana na mihutasari mipya ya masomo.";
    }

    public function getModeration(array $data): string
    {
        $op = $data['operational'];

        return "Mchakato wa utungaji na uthibitishaji wa mitihani ulifanyika kwa kufuata kanuni za kitaaluma na usiri mkubwa:\n\n" .
            "* **Uandishi wa Mitihani (Drafting):** Walimu mahiri na wazoefu walichaguliwa kutoka mikoa yote wanachama kufanya utungaji wa mitihani (Item Generation) kwa kuzingatia ramani za mitihani (Table of Specifications/Format) zilizotolewa na Baraza la Mitihani la Tanzania (NECTA).\n" .
            "* **Mapitio na Uhakiki (Moderation):** Baada ya mitihani kutungwa, zoezi la Moderation lilifanyika kitaifa/kimkoa katika Kituo Teule chini ya Kamati ya Taaluma ya Mkoa wa **" . $op['moderation_region'] . "** kwa ajili ya kufanya mapitio ya kisarufi, usahihi wa maswali, uwiano wa alama, na kuhakikisha maswali yanapima nyanja zote za utambuzi (cognitive domains).";
    }

    public function getProduction(array $data): string
    {
        $op = $data['operational'];

        return "* **Uzalishaji (Production):** Mitihani yote ilizalishwa kwa siri na usalama mkubwa chini ya usimamizi wa Kamati ya Mitihani ya Mkoa. Zoezi hili lilifanyika katika Chumba Maalum cha Siri (Strong Room / Kasiki ya Mkoa) kwa muda wa siku **" . $op['production_days'] . "** kwa kutumia mashine za chapa haraka za RISSO **" . $op['risso_machine_count'] . "** yenye thamani ya shilingi **" . number_format((float)$op['risso_machine_value']) . "**.\n" .
            "* **Ulinzi na Usambazaji (Distribution):** Baada ya kazi ya uzalishaji, kufungashwa kwa bahasha kulingana na idadi ya watahiniwa wa kila shule kukamilika, mitihani yote ilihifadhiwa kwenye Strong Room. Baadaye ilikabidhiwa kwa Maafisa Elimu wa Halmashauri na Kamati za Mitihani za Wilaya chini ya ulinzi thabiti wa Jeshi la Polisi na Maafisa Usalama wa Wilaya ili kusambazwa kwenye vituo vya mitihani kwa wakati.";
    }

    public function getExecution(array $data): string
    {
        $meta = $data['meta'];
        $op = $data['operational'];

        return "Mtihani ulianza rasmi tarehe **" . $op['exam_start_date'] . "** na kukamilika tarehe **" . $op['exam_end_date'] . "** katika shule zote zilizosajiliwa kama vituo vya mitihani.\n\n" .
            "Zoezi zima la ufanyikaji wa mitihani lilifanyika kwa kufuata ratiba sanifu iliyotolewa na Kamati ya Mkoa. Baada ya kukamilika kwa mtihani wa mwisho, wasimamizi wakuu wa vituo walikusanya skripti (karatasi za majibu) na kuzikabidhi kwa Maafisa Elimu wa Halmashauri ambao walizisafirisha chini ya ulinzi hadi Kituo Kikuu cha Usahihishaji cha Mkoa kilichopo shule ya **" . $op['marking_center'] . "**.";
    }

    public function getMarking(array $data): string
    {
        $op = $data['operational'];

        return "* **Semina na Maelekezo ya Awali:** Kabla ya kuanza kwa usahihishaji, Kamati ya Mitihani ya Mkoa chini ya Mwenyekiti wake (Afisa Elimu wa Mkoa - REO) **" . $op['reo_name'] . "** ilifanya semina ya ulinzi na maadili ya usahihishaji kwa wasahihishaji wote. Mada zilizowasilishwa ni:\n" .
            "  1. *Usalama na Usiri wa Mitihani:* Iliyowasilishwa na Mwakilishi wa Afisa Usalama wa Taifa wa Mkoa (RSO) **" . $op['rso_name'] . "**.\n" .
            "  2. *Sheria na Taratibu za Usahihishaji na Usimamizi:* Iliyowasilishwa na Afisa Taaluma/Mratibu Ndg. **" . $op['rto_name'] . "**.\n" .
            "  3. *Uratibu wa Kituo:* Uliosimamiwa na Ndg. **" . $op['exam_coordinator_name'] . "**.\n" .
            "* **Uendeshaji wa Usahihishaji:** Zoezi la usahihishaji lilifanyika kwa siku **" . $op['marking_days'] . "** na lilihusisha jumla ya wasahihishaji **" . $op['markers_count'] . "** (ikijumuisha walimu na wasaidizi wataalamu " . $op['students_assistants_count'] . "). Usahihishaji ulifanyika kwa makundi ya kimasomo (Subject Panels) kwa kutumia miongozo ya usahihishaji (Marking Schemes) iliyohakikiwa.\n" .
            "* **Uingizaji Alama kwenye Mfumo (Data Entry):** Baada ya usahihishaji wa kila somo kukamilika na kufanyiwa uhakiki wa kwanza (Audit/Verification), karatasi za alama (Score Sheets) zilikabidhiwa kwa Timu ya TEHAMA (IT Team) ya Mkoa na Halmashauri kwa ajili ya kuingiza alama (Marks Entry) kwenye Mfumo wa Usimamizi wa Matokeo wa IRMS. Mfumo huu ulifanya kazi ya kukokotoa daraja la ufaulu na wastani wa alama kwa kila mwanafunzi kiotomatiki kwa usahihi wa hali ya juu.";
    }

    public function getChallenges(): array
    {
        return [
            "**Utofauti wa Taarifa za Bahasha na Skripti:** Kubainika kwa tofauti kati ya idadi ya skripti zilizoandikwa kwenye taarifa ya nje ya bahasha na idadi halisi ya skripti zilizokutikana ndani baada ya bahasha kufunguliwa (k.m. bahasha imeandikwa ina skripti 30, lakini ndani zinapatikana skripti 40 au pungufu). Hii inaashiria uzembe wakati wa kufungasha skripti vituoni.",
            "**Kukosekana kwa ISAL (Individual Subject Attendance Log):** Baadhi ya vituo kukosa fomu rasmi za mahudhurio ya kila somo (ISAL) na karatasi za mahudhurio, hali inayofanya iwe vigumu kubaini sababu za watahiniwa wasiofanya mtihani (kushindwa kutofautisha kama watahiniwa hawakuwepo au walifanya mtihani lakini skripti zao hazikukusanywa).",
            "**Kutojaza Namba na Majina:** Watahiniwa kutoandika namba zao sahihi za usajili (Index Numbers) wala majina yao kwenye karatasi za majibu, na wengine kutumia namba zisizo zao.",
            "**Namba za Usajili Zinazojirudia (Duplicate Index Numbers):** Baadhi ya shule kuwapa watahiniwa tofauti namba moja ya usajili, jambo linaloleleta mgongano wa data wakati wa uingizaji alama kwenye mfumo wa IRMS.",
            "**Matumizi ya Karatasi Tofauti za Kujibia:** Baadhi ya shule kutumia karatasi za majibu ambazo hazina muundo sanifu (non-standard answer sheets), jambo lililosababisha ugumu na kuchelewa kwa zoezi la usahihishaji.",
            "**Makosa ya Uchapishaji (Printing Errors):** Baadhi ya karatasi za mitihani kukosa maswali au kuwa na kurasa zilizoruka wakati wa uzalishaji wa mitihani, jambo lililoathiri usawa wa watahiniwa.",
            "**Matumizi ya Lugha Isiyo Rasmi:** Watahiniwa wengine kuandika majibu kwa kutumia lugha zisizoruhusiwa au lugha za maeneo husika (k.m. Kihehe au Kisukuma) badala ya lugha rasmi ya mtihani (Kiingereza au Kiswahili kulingana na somo).",
            "**Ucheleweshaji wa Michango ya Capitation:** Baadhi ya Halmashauri kuchelewa kuwasilisha michango yao ya uendeshaji kutoka kwenye vifungu vya ruzuku (Capitation Grants), jambo lililoleta changamoto za kibajeti wakati wa uzalishaji na usahihishaji."
        ];
    }

    public function getRecommendations(): array
    {
        return [
            "**Utekelezaji wa Mfumo wa ISAL na CAL Kidijitali:** Ni lazima shule zote kupitia mfumo wa IRMS kupakua karatasi rasmi za mahudhurio (Individual Subject Attendance Logs - ISAL) na karatasi za uhakiki (Candidate Verification Logs - CAL). Wasimamizi wa vituo wahakikishe fomu hizi zinajazwa kila siku kwa kila somo kabla ya skripti kufungashwa.",
            "**Uhakiki wa Namba za Usajili Vituoni:** Wasimamizi wakuu wa vituo (Supervisors) na wasimamizi wa vyumba (Invigilators) lazima wahakiki namba za usajili za kila mtahiniwa kwa kuzilinganisha na orodha rasmi ya CAL kabla ya mtihani kuanza. Watahiniwa wabandikiwe namba zao za mtihani kwenye madawati ili kuzuia matumizi ya namba zinazojirudia au zisizo sahihi.",
            "**Kuweka Utaratibu wa Karatasi Sanifu za Kujibia (Standardized Answer Sheets):** Kamati ya Mitihani ya Mkoa inapaswa kuzalisha na kusambaza karatasi za kujibia zilizochapishwa kitaalamu yenye muundo wa kipekee unaolingana na NECTA ili kuzuia shule kutumia karatasi zisizo rasmi.",
            "**Uimarishaji wa Udhibiti wa Ubora wa Uzalishaji (QC in Printing):** Wakati wa uzalishaji kwenye Strong Room, Kamati ya Taaluma iweke jopo maalum la wahakiki (Proofreaders) kukagua sampuli za kila kundi la mitihani iliyochapishwa ili kubaini na kuondoa mitihani yenye makosa ya uchapishaji kabla ya kuwekwa kwenye bahasha.",
            "**Ujazaji wa Mahudhurio Kwenye Mfumo (Online Attendance Tracking):** Mfumo wa IRMS uboreshwe ili kuruhusu wasimamizi wa vituo au Maafisa Elimu wa Wilaya kuingiza orodha ya watahiniwa wasiofanya mitihani (Absentee Registration) kila siku wakati mtihani unaendelea. Hii itasaidia mfumo kufanya uhakiki wa papo hapo wakati wa usahihishaji.",
            "**Usimamizi wa Michango ya Capitation:** Wakurugenzi Watendaji wa Halmashauri (DEDs) na Maafisa Elimu wahakikishe fedha za Capitation zilizotengwa kwa ajili ya uendeshaji wa mitihani ya Mock zinawasilishwa kwenye akaunti ya Mkoa angalau wiki mbili kabla ya kuanza kwa maandalizi ya mtihani ili kurahisisha ununuzi wa vifaa na matengenezo ya mashine za RISSO.",
            "**Kuongeza Nguvu Kazi katika Uhakiki na Uingizaji Alama (Double Data Entry Validation):** Kuongeza idadi ya maafisa wa TEHAMA na taaluma kwenye zoezi la uingizaji alama. Mfumo wa IRMS uweke sheria ya uhakiki wa mara mbili (Double-Blind Data Entry) ambapo waingizaji wawili tofauti wanaingiza alama za kituo kimoja na mfumo kubaini tofauti yoyote kiotomatiki kabla ya alama kufungwa.",
            "**Mafunzo kwa Watahiniwa na Matumizi ya Lugha:** Walimu wakuu na walimu wa masomo wahakikishe wanafunzi wanapewa mafunzo ya kutosha ya namna ya kujibu maswali ya mitihani kwa kutumia lugha rasmi (Kiingereza au Kiswahili kulingana na somo) na kuacha kabisa kutumia lugha zisizokubalika."
        ];
    }
}
