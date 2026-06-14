<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProgress;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateService
{
    private function generateCertificateHtml(User $user, UserProgress $progress): string
    {
        $completionDate  = now()->format('d F Y');
        $memberSince     = $user->created_at->format('d F Y');
        $stageCount      = count($progress->completed_stages ?? []);
        $progressPercent = min(100, round($stageCount * 16.66, 1));
        $afroScore       = $progress->afro_score ?? 0;

        $userName       = htmlspecialchars($user->name ?? '');
        $userId         = $user->id;
        $travelLocation = htmlspecialchars($user->travel_location ?? 'Global Diaspora');
        $userPersona    = htmlspecialchars($progress->user_persona ?? 'Heritage Seeker');

        if ($stageCount >= 6) {
            $title    = 'Heritage Journey Completion';
            $subtitle = 'All 6 Stages Completed';
        } else {
            $stageWord = $stageCount === 1 ? 'Stage' : 'Stages';
            $title     = 'Heritage Journey Progress';
            $subtitle  = "{$stageCount} of 6 {$stageWord} Completed";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">

<style>

@page {
    size: letter portrait;
    margin: 0;
}

html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Georgia, serif;
    font-size: 10pt;
    background: #f0e6d0;
}

/* OUTER WRAPPER */
table.outer {
    width: 100%;
    border-collapse: collapse;
    background: #f0e6d0;
}

table.outer td {
    padding: 20pt;
    vertical-align: top;
}

/* MAIN CARD */
table.card {
    width: 100%;
    border-collapse: collapse;
    background: #fefaf3;
    border: 2pt solid #f0e6d0;
}

table.card td.inner {
    padding: 32pt 38pt 26pt 38pt;
    text-align: center;
    vertical-align: top;
}

/* RULES */
.rule {
    width: 100%;
    border: none;
    border-top: 1pt solid #c9a14a;
    margin: 10pt 0;
}

.rule-thick {
    width: 100%;
    border: none;
    border-top: 2pt solid #c9a14a;
    margin: 12pt 0;
}

/* BRAND */
.brand {
    font-size: 14pt;
    font-weight: bold;
    color: #7a2e10;
    letter-spacing: 2pt;
}

.brand-sub {
    font-size: 6pt;
    color: #b08040;
    letter-spacing: 3pt;
    text-transform: uppercase;
    margin-top: 3pt;
}

/* TITLE */
.cert-title {
    font-size: 26pt;
    font-weight: bold;
    color: #7a2e10;
    line-height: 1.15;
}

.cert-sub {
    font-size: 11pt;
    color: #c9a14a;
    font-style: italic;
    margin-top: 4pt;
}

/* RECIPIENT */
.awarded-to {
    font-size: 9pt;
    color: #555555;
    margin-bottom: 4pt;
}

.recipient-name {
    font-size: 22pt;
    font-weight: bold;
    color: #7a2e10;
    margin: 4pt 0 8pt 0;
}

.meta {
    font-size: 9pt;
    color: #777777;
    line-height: 1.9;
}

/* ACHIEVEMENT */
.achievement {
    font-size: 9pt;
    color: #444444;
    line-height: 1.75;
    font-style: italic;
}

/* PROGRESS */
.progress-box {
    background: #f7f1e4;
    border: 1pt solid #dfc07a;
    padding: 10pt 14pt;
    margin: 12pt 20pt 0 20pt;
}

.progress-lbl {
    font-size: 7pt;
    font-weight: bold;
    color: #4a4a4a;
    letter-spacing: 1pt;
    text-transform: uppercase;
    margin-bottom: 7pt;
}

.progress-track {
    width: 100%;
    background: #e2d5bc;
    height: 7pt;
}

.progress-fill {
    background: #c9a14a;
    height: 7pt;
    width: {$progressPercent}%;
}

.progress-score {
    font-size: 8pt;
    color: #666666;
    margin-top: 6pt;
}

/* STARS */
.stars {
    font-size: 16pt;
    color: #c9a14a;
    letter-spacing: 6pt;
    margin: 10pt 0 3pt 0;
}

/* FOOTER */
.fd-lbl {
    font-size: 6pt;
    text-transform: uppercase;
    letter-spacing: 1.5pt;
    color: #aaaaaa;
    margin-bottom: 3pt;
}

.fd-val {
    font-size: 8pt;
    font-weight: bold;
    color: #444444;
}

.footer-note {
    font-size: 7pt;
    color: #b08040;
    line-height: 1.5;
    margin-top: 8pt;
    padding-top: 6pt;
    border-top: 1pt solid #dfc07a;
}

</style>
</head>

<body>

<table class="outer" cellpadding="0" cellspacing="0">
    <tr>
        <td>

            <table class="card" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="inner">

                        <!-- BRAND -->
                        <div class="brand">Am&eacute; &middot; Our Roots Africa</div>
                        <div class="brand-sub">Heritage Journey Platform</div>

                        <hr class="rule-thick" style="margin-top:16pt;">

                        <!-- TITLE -->
                        <div class="cert-title">$title</div>
                        <div class="cert-sub">$subtitle</div>

                        <hr class="rule-thick">

                        <!-- RECIPIENT -->
                        <div class="awarded-to">
                            This Certificate is Proudly Awarded to
                        </div>

                        <div class="recipient-name">$userName</div>

                        <div class="meta">
                            <div>Member since $memberSince</div>
                            <div>$travelLocation</div>
                            <div style="margin-top:3pt;">
                                Heritage Identity:
                                <strong>$userPersona</strong>
                            </div>
                        </div>

                        <hr class="rule">

                        <!-- ACHIEVEMENT -->
                        <div class="achievement">
                            For successfully progressing through the Am&eacute;
                            Heritage Journey Program. Through dedicated engagement
                            and personal reflection, you are deepening your
                            connection to your heritage and preparing for meaningful
                            cultural immersion.
                        </div>

                        <!-- PROGRESS -->
                        <div class="progress-box">

                            <div class="progress-lbl">
                                Journey Progress &mdash; $stageCount of 6 Stages
                            </div>

                            <div class="progress-track">
                                <div class="progress-fill"></div>
                            </div>

                            <div class="progress-score">
                                Readiness Score:
                                <strong>$afroScore / 100</strong>
                            </div>

                        </div>

                        <!-- STARS -->
                        <div class="stars">
                            &#9733; &nbsp; &#9733; &nbsp; &#9733;
                        </div>

                        <hr class="rule">

                        <!-- FOOTER -->
                        <table width="100%" cellpadding="0" cellspacing="0">
                            <tr>

                                <td width="50%" style="text-align:center;">
                                    <div class="fd-lbl">
                                        Certificate Date
                                    </div>

                                    <div class="fd-val">
                                        $completionDate
                                    </div>
                                </td>

                                <td width="50%" style="text-align:center;">
                                    <div class="fd-lbl">
                                        Certificate ID
                                    </div>

                                    <div class="fd-val">
                                        AME-$userId-{$stageCount}S
                                    </div>
                                </td>

                            </tr>
                        </table>

                        <div class="footer-note">
                            This certificate recognizes the holder&rsquo;s
                            commitment to understanding and honoring their
                            African heritage.
                        </div>

                    </td>
                </tr>
            </table>

        </td>
    </tr>
</table>

</body>
</html>
HTML;
    }

    private function generateCustodianCertificateHtml(User $user, UserProgress $progress): string
    {
        $completionDate = now()->format('d F Y');
        $userName = htmlspecialchars($user->name ?? '');

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page {
    size: letter portrait;
    margin: 0;
}
html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    background: #0b1a12;
}
* {
    box-sizing: border-box;
}
body {
    font-family: Georgia, serif;
    background: #0b1a12;
    color: #f0ebe0;
}
table.outer {
    width: 100%;
    border-collapse: collapse;
    background: #0b1a12;
}
table.outer td {
    padding: 24pt;
    vertical-align: top;
}
table.card {
    width: 100%;
    border-collapse: collapse;
    background: #11261b;
    border: 5pt double #c9a14a;
}
table.card td.inner {
    padding: 40pt 30pt;
    text-align: center;
    vertical-align: top;
}
.eyebrow {
    color: #c9a14a;
    font-size: 10pt;
    letter-spacing: 2pt;
    text-transform: uppercase;
    margin-bottom: 20pt;
    font-weight: bold;
}
.title {
    font-size: 24pt;
    color: #f0ebe0;
    margin-bottom: 8pt;
    font-weight: normal;
}
.subtitle {
    font-style: italic;
    font-size: 11pt;
    color: #8a7f72;
    margin-bottom: 16pt;
}
.name {
    font-size: 22pt;
    font-weight: bold;
    color: #c9a14a;
    border-bottom: 1.5pt solid rgba(201,161,74,0.3);
    display: inline-block;
    padding-bottom: 4pt;
    margin-bottom: 16pt;
}
.description {
    font-size: 11pt;
    color: #c5bfae;
    line-height: 1.7;
    margin: 0 auto 20pt;
    padding: 0 20pt;
}
.divider {
    border: none;
    border-top: 1pt solid rgba(201,161,74,0.2);
    margin: 16pt auto;
    width: 80%;
}
.meta-table {
    width: 80%;
    margin: 16pt auto 0;
    border-collapse: collapse;
}
.meta-table td {
    width: 50%;
    text-align: center;
}
.meta-label {
    font-size: 8pt;
    color: #8a7f72;
    letter-spacing: 1pt;
    margin-bottom: 4pt;
    text-transform: uppercase;
}
.meta-value {
    font-size: 11pt;
    color: #f0ebe0;
    font-weight: bold;
}
</style>
</head>
<body>

<table class="outer" cellpadding="0" cellspacing="0">
    <tr>
        <td>
            <table class="card" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="inner">
                        <div class="eyebrow">OurRoots Africa Custodian Certificate</div>
                        
                        <div class="title">Afrofeast Certified Custodian</div>
                        <div class="subtitle">This certifies that</div>
                        
                        <div class="name">$userName</div>
                        
                        <div class="description">
                            has successfully completed all modules of the <strong>Heritage Training Programme</strong>.
                            Having demonstrated profound cultural grounding, emotional first-aid readiness, and radical transparency protocols,
                            they are officially certified as a premium heritage guardian.
                        </div>
                        
                        <hr class="divider" />
                        
                        <table class="meta-table">
                            <tr>
                                <td>
                                    <div class="meta-label">Issued Date</div>
                                    <div class="meta-value">$completionDate</div>
                                </td>
                                <td>
                                    <div class="meta-label">Credential Status</div>
                                    <div class="meta-value" style="color: #4ade80;">Active & Verified</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
HTML;
    }

    public function generatePdf(User $user, UserProgress $progress)
    {
        try {
            if ($user->role === 'custodian') {
                $html = $this->generateCustodianCertificateHtml($user, $progress);
            } else {
                $html = $this->generateCertificateHtml($user, $progress);
            }

            $pdf = Pdf::loadHTML($html)
                ->setPaper('letter', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => false,
                    'defaultFont'          => 'serif',
                    'dpi'                  => 96,
                ]);

            return $pdf;

        } catch (\Exception $e) {

            throw new \Exception(
                'Certificate generation failed: ' . $e->getMessage()
            );
        }
    }

    public function getCertificateInfo(User $user, UserProgress $progress): array
    {
        $completedStages = $progress->completed_stages ?? [];
        $stageCount      = count($completedStages);

        return [
            'eligible'        => $stageCount > 0,
            'completedStages' => $stageCount,
            'totalStages'     => 6,
            'progress'        => ($stageCount / 6) * 100,
            'afroScore'       => $progress->afro_score ?? 0,

            'title' => $stageCount >= 6
                ? 'Heritage Journey Completion Certificate'
                : 'Heritage Journey Progress Certificate',

            'completionDate' => now()->format('d F Y'),
        ];
    }
}