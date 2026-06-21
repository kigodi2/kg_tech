<?php

namespace App\Services\Results;

class ZonalResultBookNarrativeService
{
    public function getExecutiveSummary(array $data): string
    {
        $meta = $data['meta'];
        $profile = $data['zone_profile'];
        $att = $data['attendance'];
        $perf = $data['performance'];

        $totalSchools = number_format($profile['total_schools']);
        $registered = number_format($att['registered_total']);
        $satTotal = number_format($att['sat_total']);
        $attendanceRate = number_format($att['attendance_rate'], 2);

        $passAC = $perf['regional']['pass'] ?? 0;
        $satVal = $perf['regional']['sat'] ?? 0;
        $passRate = $satVal > 0 ? number_format(($passAC / $satVal) * 100, 2) : '0.00';

        $topRegion = isset($perf['regions'][0]) ? $perf['regions'][0]['name'] : 'N/A';
        $topRegionAvg = isset($perf['regions'][0]) ? number_format($perf['regions'][0]['average_marks'], 2) : '0.00';

        $topSchool = isset($perf['top_schools'][0]) ? $perf['top_schools'][0]['name'] : 'N/A';
        $topSchoolAvg = isset($perf['top_schools'][0]) ? number_format($perf['top_schools'][0]['average_marks'], 2) : '0.00';

        return "Ripoti hii inatoa muhtasari wa matokeo ya mtihani wa utamilifu wa Darasa la Saba (PSLE Mock) kwa Mwaka " . $meta['exam_year'] . " katika Kanda ya Academic Zone ya **TASIDO** (inayojumuisha Mikoa ya Tabora, Singida, Iringa na Dodoma). " .
            "Mtihani huu ulihusisha jumla ya shule **" . $totalSchools . "** na watahiniwa **" . $registered . "** waliosajiliwa. " .
            "Kati yao, watahiniwa **" . $satTotal . "** walifanya mtihani, ikiwa ni sawa na asilimia **" . $attendanceRate . "%** ya mahudhurio ya jumla. " .
            "Ufaulu wa jumla wa Kanda (Daraja A-D) umefikia asilimia **" . $passRate . "%** ya watahiniwa wote waliofanya mtihani. " .
            "Mkoa ulioongoza kitaaluma katika Kanda ni **" . $topRegion . "** ukiwa na wastani wa alama **" . $topRegionAvg . "**, na shule iliyoongoza kikanda ni **" . $topSchool . "** yenye wastani wa alama **" . $topSchoolAvg . "**. " .
            "Tathmini ya kina ya takwimu za usajili, mahudhurio, utendaji wa kimasomo, kiutawala na kiumiliki imeainishwa kikamilifu katika kurasa zinazofuata.";
    }

    public function getIntroduction(array $data): string
    {
        $meta = $data['meta'];
        $profile = $data['zone_profile'];
        $att = $data['attendance'];

        $regionsText = "1. Mkoa wa Tabora\n2. Mkoa wa Singida\n3. Mkoa wa Iringa\n4. Mkoa wa Dodoma\n";

        $satPct = $att['attendance_rate'];
        $absPct = $att['registered_total'] > 0 ? round(($att['absent_total'] / $att['registered_total']) * 100, 2) : 0.0;

        return "Kanda ya Academic Zone ya **TASIDO** ina jumla ya shule za Msingi **" . number_format($profile['total_schools']) . "** ambapo shule za Serikali ni **" . number_format($profile['government_schools']) . "** na shule za Binafsi/Zisizo za Serikali ni **" . number_format($profile['private_schools']) . "**. Kiutawala, Kanda hii inajumuisha Mikoa minne (4) ambayo ni:\n" .
            $regionsText . "\n" .
            "Kanda yetu ina jumla ya Halmashauri/Wilaya **" . number_format($profile['councils_count']) . "**.\n\n" .
            "Jumla ya shule **" . number_format($profile['total_schools']) . "** zenye watahiniwa wa Darasa la Saba wa mwaka **" . $meta['exam_year'] . "** zilifanya Mtihani wa Utamilifu wa Kanda (Mock).\n\n" .
            "Jumla ya watahiniwa **" . number_format($att['registered_total']) . "** walisajiliwa kufanya mtihani huu, ikijumuisha Wavulana **" . number_format($att['registered_male']) . "** na Wasichana **" . number_format($att['registered_female']) . "**.\n\n" .
            "Kati ya watahiniwa waliosajiliwa, jumla ya watahiniwa **" . number_format($att['sat_total']) . "** walifanya mtihani huo, ikijumuisha Wavulana **" . number_format($att['sat_male']) . "** na Wasichana **" . number_format($att['sat_female']) . "**, ikiwa ni sawa na asilimia **" . number_format($satPct, 2) . "%** ya watahiniwa wote waliosajiliwa. Jumla ya watahiniwa **" . number_format($att['absent_total']) . "** (Wavulana **" . number_format($att['absent_male']) . "**, Wasichana **" . number_format($att['absent_female']) . "**) sawa na asilimia **" . number_format($absPct, 2) . "%** hawakufanya mtihani kutokana na sababu mbalimbali kama vile utoro, ugonjwa na sababu nyingine za kijamii.\n\n" .
            "Mtihani huu wa Utamilifu unafanyika kwa malengo makuu yafuatayo:\n" .
            "* **Kupima Maendeleo ya Kitaaluma:** Kuwapima watahiniwa kitaaluma ili kubaini kiwango cha uelewa wa mada zilizofundishwa tanzu kuanza kwa mzunguko wa masomo yao na kuandaa mikakati ya kurekebisha mapungufu kabla ya mtihani wa Taifa.\n" .
            "* **Kupima Utendaji Kazi (KPIs):** Kutathmini utendaji kazi wa walimu (Key Performance Indicators) katika ufundishaji na ujifunzaji na kuweka mikakati ya pamoja kikanda.\n" .
            "* **Kuzoea Mazingira ya Mitihani:** Kuwajengea watahiniwa ujasiri na uzoefu wa muundo na sheria za mitihani ya Taifa ili kupunguza taharuki wakati wa mtihani wa mwisho.";
    }

    public function getPreparations(array $data): string
    {
        $op = $data['operational'];

        return "Maandalizi ya mtihani yalianza kwa uratibu na vikao vya pamoja vilivyowashirikisha Maafisa Elimu wa Mikoa na Halmashauri (REOs/DEOs), Maafisa Taaluma (RTOs/DTOs), na Wathibiti Ubora wa Shule katika Kanda. Vikao hivyo vililenga kukubaliana juu ya miongozo ya uendeshaji, usimamizi, usahihishaji, na mifumo ya bajeti.\n\n" .
            "Katika vikao hivyo, makubaliano yafuatayo yalifikiwa:\n" .
            "1. **Uratibu wa Bajeti:** Kikao kiliazimia kuweka na kupitisha bajeti ya jumla ya shilingi **" . number_format((float)$op['budget_amount']) . "** kwa ajili ya uzalishaji wa mitihani, ununuzi wa karatasi na vifaa vya ofisi ikijumuisha matengenezo na uendeshaji wa mashine kubwa za chapa (RISSO) ili kurahisisha zoezi la uzalishaji. Bajeti hii ilichangiwa kutoka kwenye vifungu vya ruzuku ya shule (Capitation Grants) na michango maalum ya uendeshaji ya kila Mkoa/Halmashauri.\n" .
            "2. **Ushirikiano wa Kikanda (Zonal Collaboration):** Mtihani huu wa utamilifu uliandaliwa kupitia ushirikiano wa Kanda ya **" . $op['collaborating_regions'] . "**, ambapo mikoa iligawana majukumu ya uandaaji wa rasimu za awali za mitihani ya masomo yote kulingana na mihutasari mipya ya masomo.";
    }

    public function getModeration(array $data): string
    {
        $op = $data['operational'];

        return "Mchakato wa utungaji na uthibitishaji wa mitihani ulifanyika kwa kufuata kanuni za kitaaluma na usiri mkubwa:\n\n" .
            "* **Uandishi wa Mitihani (Drafting):** Walimu mahiri na wazoefu walichaguliwa kutoka mikoa yote wanachama kufanya utungaji wa mitihani (Item Generation) kwa kuzingatia ramani za mitihani (Table of Specifications/Format) zilizotolewa na Baraza la Mitihani la Tanzania (NECTA).\n" .
            "* **Mapitio na Uhakiki (Moderation):** Baada ya mitihani kutungwa, zoezi la Moderation lilifanyika katika Kituo Teule chini ya Kamati ya Taaluma ya Mkoa wa **" . $op['moderation_region'] . "** kwa ajili ya kufanya mapitio ya kisarufi, usahihi wa maswali, uwiano wa alama, na kuhakikisha maswali yanapima nyanja zote za utambuzi (cognitive domains).";
    }

    public function getProduction(array $data): string
    {
        $op = $data['operational'];

        return "* **Uzalishaji (Production):** Mitihani yote ilizalishwa kwa siri na usalama mkubwa chini ya usimamizi wa Kamati ya Mitihani ya Kanda. Zoezi hili lilifanyika katika Chumba Maalum cha Siri (Strong Room) kwa muda wa siku **" . $op['production_days'] . "** kwa kutumia mashine za chapa haraka za RISSO **" . $op['risso_machine_count'] . "** yenye thamani ya shilingi **" . number_format((float)$op['risso_machine_value']) . "**.\n" .
            "* **Ulinzi na Usambazaji (Distribution):** Baada ya kazi ya uzalishaji, kufungashwa kwa bahasha kulingana na idadi ya watahiniwa wa kila shule kukamilika, mitihani yote ilihifadhiwa kwenye Strong Room. Baadaye ilikabidhiwa kwa Maafisa Elimu wa Wilaya na Kamati za Mitihani chini ya ulinzi thabiti wa Jeshi la Polisi na Maafisa Usalama wa Wilaya ili kusambazwa kwenye vituo vya mitihani kwa wakati.";
    }

    public function getExecution(array $data): string
    {
        $op = $data['operational'];

        return "Mtihani ulianza rasmi tarehe **" . $op['exam_start_date'] . "** na kukamilika tarehe **" . $op['exam_end_date'] . "** katika shule zote zilizosajiliwa kama vituo vya mitihani.\n\n" .
            "Zoezi zima la ufanyikaji wa mitihani lilifanyika kwa kufuata ratiba sanifu iliyotolewa na Kamati ya Kanda. Baada ya kukamilika kwa mtihani wa mwisho, wasimamizi wakuu wa vituo walikusanya skripti (karatasi za majibu) na kuzikabidhi kwa Maafisa Elimu wa Wilaya ambao walizisafirisha chini ya ulinzi hadi Kituo Kikuu cha Usahihishaji cha Kanda kilichopo shule ya **" . $op['marking_center'] . "**.";
    }

    public function getMarking(array $data): string
    {
        $op = $data['operational'];

        return "* **Semina na Maelekezo ya Awali:** Kabla ya kuanza kwa usahihishaji, Kamati ya Mitihani ya Kanda chini ya Mwenyekiti wake ilifanya semina ya ulinzi na maadili ya usahihishaji kwa wasahihishaji wote. Mada zilizowasilishwa ni:\n" .
            "  1. *Usalama na Usiri wa Mitihani:* Iliyowasilishwa na Mwakilishi wa RSO **" . $op['rso_name'] . "**.\n" .
            "  2. *Sheria na Taratibu za Usahihishaji na Usimamizi:* Iliyowasilishwa na Afisa Taaluma Ndg. **" . $op['rto_name'] . "**.\n" .
            "  3. *Uratibu wa Kituo:* Uliosimamiwa na Ndg. **" . $op['exam_coordinator_name'] . "**.\n" .
            "* **Uendeshaji wa Usahihishaji:** Zoezi la usahihishaji lilifanyika kwa siku **" . $op['marking_days'] . "** na lilihusisha jumla ya wasahihishaji **" . $op['markers_count'] . "** (ikijumuisha walimu na wasaidizi wataalamu " . $op['students_assistants_count'] . "). Usahihishaji ulifanyika kwa makundi ya kimasomo (Subject Panels) kwa kutumia miongozo ya usahihishaji (Marking Schemes) iliyohakikiwa.\n" .
            "* **Uingizaji Alama kwenye Mfumo (Data Entry):** Baada ya usahihishaji wa kila somo kukamilika na kufanyiwa uhakiki wa kwanza (Audit/Verification), karatasi za alama (Score Sheets) zilikabidhiwa kwa Timu ya TEHAMA (IT Team) kwa ajili ya kuingiza alama (Marks Entry) kwenye Mfumo wa Usimamizi wa Matokeo wa IRMS. Mfumo huu ulifanya kazi ya kukokotoa daraja la ufaulu na wastani wa alama kwa kila mwanafunzi kiotomatiki kwa usahihi wa hali ya juu.";
    }

    public function getChallenges(): array
    {
        return [
            "**Utofauti wa Taarifa za Bahasha na Skripti:** Kubainika kwa tofauti kati ya idadi ya skripti zilizoandikwa kwenye taarifa ya nje ya bahasha na idadi halisi ya skripti zilizokutikana ndani baada ya bahasha kufunguliwa. Hii inaashiria uzembe wakati wa kufungasha skripti vituoni.",
            "**Kukosekana kwa ISAL (Individual Subject Attendance Log):** Baadhi ya vituo kukosa fomu rasmi za mahudhurio ya kila somo (ISAL) na karatasi za mahudhurio, hali inayofanya iwe vigumu kubaini sababu za watahiniwa wasiofanya mtihani.",
            "**Kutojaza Namba na Majina:** Watahiniwa kutoandika namba zao sahihi za usajili (Index Numbers) wala majina yao kwenye karatasi za majibu, na wengine kutumia namba zisizo zao.",
            "**Namba za Usajili Zinazojirudia (Duplicate Index Numbers):** Baadhi ya shule kuwapa watahiniwa tofauti namba moja ya usajili, jambo linaloleleta mgongano wa data wakati wa uingizaji alama kwenye mfumo wa IRMS.",
            "**Matumizi ya Karatasi Tofauti za Kujibia:** Baadhi ya shule kutumia karatasi za majibu ambazo hazina muundo sanifu, jambo lililosababisha ugumu na kuchelewa kwa zoezi la usahihishaji.",
            "**Makosa ya Uchapishaji (Printing Errors):** Baadhi ya karatasi za mitihani kukosa maswali au kuwa na kurasa zilizoruka wakati wa uzalishaji wa mitihani.",
            "**Matumizi ya Lugha Isiyo Rasmi:** Watahiniwa wengine kuandika majibu kwa kutumia lugha zisizoruhusiwa au lugha za maeneo husika badala ya lugha rasmi ya mtihani.",
            "**Ucheleweshaji wa Michango ya Capitation:** Baadhi ya Halmashauri kuchelewa kuwasilisha michango yao ya uendeshaji kutoka kwenye vifungu vya ruzuku, jambo lililoleta changamoto za kibajeti wakati wa uzalishaji na usahihishaji."
        ];
    }

    public function getRecommendations(): array
    {
        return [
            "**Utekelezaji wa Mfumo wa ISAL na CAL Kidijitali:** Ni lazima shule zote kupitia mfumo wa IRMS kupakua karatasi rasmi za mahudhurio (ISAL) na karatasi za uhakiki (CAL). Wasimamizi wa vituo wahakikishe fomu hizi zinajazwa kila siku kwa kila somo kabla ya skripti kufungashwa.",
            "**Uhakiki wa Namba za Usajili Vituoni:** Wasimamizi wakuu wa vituo na wasimamizi wa vyumba lazima wahakiki namba za usajili za kila mtahiniwa kwa kuzilinganisha na orodha rasmi ya CAL kabla ya mtihani kuanza.",
            "**Kuweka Utaratibu wa Karatasi Sanifu za Kujibia:** Kamati ya Mitihani ya Kanda inapaswa kuzalisha na kusambaza karatasi za kujibia zilizochapishwa kitaalamu yenye muundo wa kipekee unaolingana na NECTA.",
            "**Uimarishaji wa Udhibiti wa Ubora wa Uzalishaji:** Wakati wa uzalishaji kwenye Strong Room, Kamati ya Taaluma iweke jopo maalum la wahakiki kukagua sampuli za kila kundi la mitihani iliyochapishwa ili kubaini na kuondoa mitihani yenye makosa ya uchapishaji.",
            "**Ujazaji wa Mahudhurio Kwenye Mfumo:** Mfumo wa IRMS uboreshwe ili kuruhusu wasimamizi wa vituo au Maafisa Elimu wa Wilaya kuingiza orodha ya watahiniwa wasiofanya mitihani kila siku wakati mtihani unaendelea.",
            "**Usimamizi wa Michango ya Capitation:** Wakurugenzi Watendaji wa Halmashauri na Maafisa Elimu wahakikishe fedha za Capitation zilizotengwa kwa ajili ya uendeshaji zinawasilishwa kwenye akaunti ya Kanda angalau wiki mbili kabla ya kuanza kwa maandalizi.",
            "**Kuongeza Nguvu Kazi katika Uhakiki na Uingizaji Alama:** Kuongeza idadi ya maafisa wa TEHAMA na taaluma kwenye zoezi la uingizaji alama kwa kutumia mfumo wa Double-Blind Data Entry.",
            "**Mafunzo kwa Watahiniwa na Matumizi ya Lugha:** Walimu wakuu na walimu wa masomo wahakikishe wanafunzi wanapewa mafunzo ya kutosha ya namna ya kujibu maswali ya mitihani kwa kutumia lugha rasmi."
        ];
    }
}
