<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dokumentai - {{ $claim->claim_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7pt;
            line-height: 1.1;
            color: #000;
            margin: 40px;
        }
        .page-break { page-break-after: always; }
        .info-block {
            margin-bottom: 20px;
            text-align: center;

        }
        .left-align {
            text-align: left;
        }
        .center-align { text-align: center; margin: 30px 0; }
        .bold { font-weight: bold; }
        .signature-wrapper {
            margin-top: 100px;
            width: 100%;
            display: block;
        }

        .signature-line {
            width: 300px;
            margin-left: auto;
            text-align: right;
        }

        .line {
            border-top: 1px solid #000;
            margin-bottom: 5px;
        }

        .caption {
            /*font-size: 9pt;*/
        }
        p { margin: 5px 0; }
    </style>
</head>
<body>

<div class="page-break">
    <div class="info-block">
        <div>{{ $claim->first_name }} {{ $claim->last_name }}</div>
        <div>a/k/ įm.k: {{ $claim->personal_code }}</div>
        <div>Adresas: {{ $claim->address }}</div>
    </div>

    <div style="line-height: 8px;margin-top: 60px;margin-bottom: 60px;" class="info-block left-align">
        <p class="bold">{{ $claim->partner->name }}</p>
        <p>Žalų reguliavimo skyriui</p>
        <p>Adresas: {{ $claim->partner->address }}</p>
        <p>Įmonės kodas: {{ $claim->partner->company_code }}</p>
    </div>

    <div class="center-align bold">
        <div class="bold">Prašymas</div>
        <div>{{ now()->translatedFormat('Y \m. F j \d.') }}</div>
        <div>Vilnius</div>
    </div>

    <div class="content">
        <p>Informuojame Jus, jog dėl eismo įvykio (žalos numeris <span class="bold">{{ $claim->claim_number }}</span>) netekau galimybės naudotis man priklausančiu automobiliu.

        Automobilis atiduotas remonto įmonei <span class="bold">{{ $claim->garage->name }}</span> š.m. <span class="bold">{{ \Carbon\Carbon::parse($claim->rental_start)->format('Y-m-d') }}</span>.

        Tol, kol automobilis yra remontuojamas, esu priverstas (-a) nuomotis pakaitinį automobilį tam, kad naudojamo tarnybinio automobilio netektis būtų atkurta į padėtį buvusią prieš įvykį, už kurį atsakingas asmuo yra apsidraudęs privalomuoju civilinės vairuotojo atsakomybės draudimu Jūsų Draudimo bendrovėje.

        Pakaitinį automobilį išsinuomavome iš įmonės UAB „BMS Technologija“.</p>
    </div>

    <div class="signature-wrapper">
        <div class="signature-line">
            <div class="line"></div>
            <p class="caption">(vardas, pavardė, parašas)</p>
        </div>
    </div>
</div>

<div>
    <div class="info-block">
        <div>{{ $claim->first_name }} {{ $claim->last_name }}</div>
        <div>a/k/ įm.k: {{ $claim->personal_code }}</div>
        <div>Adresas: {{ $claim->address }}</div>
    </div>

    <div style="line-height: 8px;margin-top: 60px;margin-bottom: 60px;" class="info-block left-align">
        <p class="bold">{{ $claim->partner->name }}</p>
        <p>Žalų reguliavimo skyriui</p>
        <p>Adresas: {{ $claim->partner->address }}</p>
        <p>Įmonės kodas: {{ $claim->partner->company_code }}</p>
    </div>

    <div class="center-align bold">
        <div class="bold">Įgaliojimas</div>
        <div>{{ now()->translatedFormat('Y \m. F j \d.') }}</div>
        <div>Vilnius</div>
    </div>

    <div class="content">
        <p>
            <span class="bold">{{ $claim->first_name }} {{ $claim->last_name }}</span>
            a/k/ įm.k: {{ $claim->personal_code }}, įgalioja UAB „BMS Technologija“ direktorių <strong>Mykolą Sabaitį</strong>,
            a/k: <strong>38405150075</strong>, teikti visus reikiamus dokumentus ir atstovauti
            <span class="bold">{{ $claim->first_name }} {{ $claim->last_name }}</span>
            a/k/ įm.k: {{ $claim->personal_code }}, susirašinėjant el. paštu dėl pakaitinio automobilio nuomos atlyginimo pagal šį eismo įvykį (žalos numeris <span class="bold">{{ $claim->claim_number }}</span>).
        </p>
    </div>

    <div class="signature-wrapper">
        <div class="signature-line">
            <div class="line"></div>
            <p class="caption">(vardas, pavardė, parašas)</p>
        </div>
    </div>
</div>

</body>
</html>
