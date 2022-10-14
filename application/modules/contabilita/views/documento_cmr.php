<?php
$destinatario = json_decode($doc['documenti_contabilita_destinatario'], true);
// debug($destinatario, true);
// debug($doc, true);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Esemplare per il mittente</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" />

    <style>
        /*p { font-size: 1.2em; }*/
        /*.row { padding-top: 20px; }*/
        p {
            font-size: 0.8em;
            line-height: 1.2em;
        }

        .content_center {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .insert {
            color: rgb(6, 6, 190);
            font-size: 1.1em;
        }

        .flex_cont {
            display: flex;
            justify-content: space-between;
        }

        .border {
            border: 1px solid #000;
        }

        .number {
            font-size: 1.1em;
            font-weight: bold;
        }

        .space_between {
            display: flex;
            justify-content: space-between;
        }

        .space_evenly {
            display: flex;
            justify-content: space-evenly;
        }

        table th,
        table tr,
        table tr td {
            font-size: 0.9em;
            border: 1px solid black;
        }

        table th {
            text-align: center;
        }

        .row-eq-height {
            display: -webkit-box;
            display: -webkit-flex;
            display: -ms-flexbox;
            display: flex;
        }

        .row {
            overflow: hidden;
        }

        [class*="col-"] {
            margin-bottom: -99999px;
            padding-bottom: 99999px;
        }

        .square {
            width: 20px;
            height: 20px;
            border: 1px solid #000;
        }

        .new_page {
            page-break-after: always;
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <div class="container-fluid new_page">
        <div class="row">
            <div class="col-md-1 number">
                1
            </div>
            <div class="col-md-6">
                <p>Esemplare per il Mittente - Exemplaire de l'expediteur - Copy for sender</p>
            </div>
            <div class="col-md-5">
                <p>Pagina 1 di </p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">1</div>
                    <div class="col-md-11">
                        <p>Mittente (nome, domicilio, paese)</br>Expediteur (nom, adresse, pays)</br>Sender (name,
                            addresse,
                            country)</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GRUPPO FRANCESCHINO LORIS s.r.l</b></br>VIA TRASAGHIS, 180</br>33103 -
                            GEMONA
                            (UD)</br>ITALY</br>TEL. +39 0432 981167</p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-6">
                        <p>Lettera di vettura internazionale</br>Lettre de voiture internacionale</br>International
                            consignment note</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>Questo trasporto è sottoposto,</br>nonostante tutte le clausole contrarie,</br>alla
                            Convenzione del Trasp. Stradale</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p class="insert"><?php echo $doc['documenti_contabilita_tracking_code'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <!-- <span style="width: 200px; height: 100px; border: 2px solid black; border-radius: 100px / 50px;">CMR</span> -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p>Ce transport est soumis à la</br>Convention relative au contrat</br>de transport
                            International de</br>marchandises par route.</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>This carriage is subjecte to the</br>convention on the contract for the</br>Inter. Carriage
                            of goods by road.</p>
                    </div>
                </div>
            </div>
        </div>

        <!--2/16-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">2</div>
                    <div class="col-md-11">
                        <p>Destinatario (nome, domicilio, paese)</br>Destinataire (nom, adresse, pays)</br>Consignee
                            (name,
                            addresse, country)</p>
                        <p class="insert"><b><?php echo $destinatario['ragione_sociale'] ?></b></br><?php echo $destinatario['indirizzo'] ?>
                            </br><?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></br><?php echo strtoupper($destinatario['nazione']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-bottom: 0;">
                <div class="row">
                    <div class="col-md-1 number">16</div>
                    <div class="col-md-11">
                        <p>Trasportatore (nome, domicilio, paese)</br>Transporteur (nom, adresse, pays)</br>Carrier
                            (name,
                            addresse, country)</p>
                        <p class="insert"><?php echo $doc['documenti_contabilita_trasporto_a_cura_di'] ?></br><?php echo $doc['documenti_contabilita_vettori_residenza_domicilio'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!--3/17-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">3</div>
                    <div class="col-md-11">
                        <p>Luogo di consegna delle merci</br>Lieu pour la livraison de la marchandise</br>Place of
                            delivery of
                            the goods</p>
                        <?php if (!empty($doc['documenti_contabilita_luogo_destinazione']) && $doc['documenti_contabilita_luogo_destinazione'] !== 'MEDESIMO') : ?>
                            <p class="insert"><b><?php echo $doc['documenti_contabilita_luogo_destinazione'] ?></b></p>
                        <?php else : ?>
                            <p class="insert"><b><?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></b></br><?php echo $destinatario['nazione'] ?></br>(come sopra/as above)</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-top: 0">
                <div class="row">
                    <div class="col-md-1 number">17</div>
                    <div class="col-md-11">
                        <p>Trasportatore successivo (nome, domicilio, paese)</br>Transporteur succesives (nom, adresse,
                            pays)</br>Successive carriers (name,
                            addresse, country)</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">4</div>
                    <div class="col-md-11">
                        <p>Luogo di presa in consegna delle merci</br>Lieu de la prise en charge de la
                            marchandise</br>Place of taking over the goods</p>
                        <p class="insert"><b>33013 - GEMONA (UD)</b></br>ITALY</br>(come sopra/as above)</p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000;">
                    <div class="col-md-11 col-md-offset-1">
                        <p>Documenti allegati</br>Documents annexille</br>Documents attached</p>
                        <p class="insert"><?php echo "FATTURA / INVOICE {$doc['documenti_contabilita_numero']}-" . date('Y', strtotime($doc['documenti_contabilita_data_emissione'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border">
                <div class="row">
                    <div class="col-md-1 number">18</div>
                    <div class="col-md-11">
                        <p>Riserve ed osservazioni del corriere</br>Réserves ed observations du
                            transporteur</br>Carrer's reservations and observations</p>
                        <p class="insert">
                            <?php echo $doc['documenti_contabilita_annotazioni_trasporto'] ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0; border: 1px solid #000;">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 space_evenly">
                        <div class="number">6</div>
                        <p>Contrassegni e numeri</br>Marques et numéros</br>Marks and number</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">7</div>
                        <p>Numero dei colli</br>Nombre des colls</br>Number of packages</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">8</div>
                        <p>tipo di imballaggi</br>Mode d'emballage</br>Method of packin</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">9</div>
                        <p>Descrizione delle merci</br>Nature de la marchandise</br>Nature of the goods</p>
                    </div>
                </div>
                <div class="row" style="padding-bottom: 50px">
                    <div class="col-md-9 col-md-offset-3">
                        <p class="insert"><b>Tot. <?php echo $doc['documenti_contabilita_n_colli'] ?></b> <?php echo $doc['documenti_contabilita_descrizione_colli'] ?></p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000; border-right: 1px solid #000;">
                    <div class="col-md-3">
                        <p>Classe</br>Class</p>
                    </div>
                    <div class="col-md-3">
                        <p>Cifre</br>Number</p>
                    </div>
                    <div class="col-md-3">
                        <p>Lettere</br>Letter</p>
                    </div>
                    <div class="col-md-3">
                        <p>F</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-left: 1px solid #000;">
                <div class="row">
                    <div class="col-md-4 space_between">
                        <div class="number">10</div>
                        <p>Numero statistico</br>N° statistique</br>N° statistic</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">11</div>
                        <p>Peso lordo Kg.</br>Polds brut Kg.</br>Gross Weight Kg.</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">12</div>
                        <p>Volume m3</br>Cubage m3</br>Volume in m3</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 content_center">
                        <p class="insert"></p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_peso'], 2, ',', ''); ?></p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_volume'], 2, ',', ''); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!--fine 4/18 row-->

        <!--13/19-->
        <div class="row" style=" padding-top: 0; border-right: 1px solid black;">
            <div class="col-md-5 border" style="height: 100%; border-top: 0">
                <div class="row" style="border-bottom: 1px solid #000">
                    <div class=" col-md-1 number">13</div>
                    <div class="col-md-11">
                        <p>Istruzioni del mittente</br>Instructions de l'expediteur</br>Sender's informations</p>
                        <p class="insert"><b><?php echo (isset($doc['clienti_referente']) && !empty($doc['clienti_referente'])) ? $doc['clienti_referente'] : ''; ?></b></br><?php echo (isset($doc['clienti_referente_telefono']) && !empty($doc['clienti_referente_telefono'])) ? 'TEL. ' . $doc['clienti_referente_telefono'] : ''; ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 number">14</div>
                    <div class="col-md-11">
                        <p>Tipo di pagamento</br>Presentactions d'affranchasement</br>Instructions as to payment
                            carriage
                        </p>
                        <div class="row">
                            <div class="col-md-6">
                                <p><b>Porto franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (!empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-6">
                                <p><b>Porto non franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7" style="border-top:0;">
                <div class="row">
                    <div class="col-md-1 number">19</div>
                    <div class="col-md-4">
                        <p>Convenzioni particolari</br>Conventions particulières</br>Special agreement</p>
                    </div>
                    <div class="col-md-7">
                        <p class="insert"></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 number">20</div>
                    <div class="col-md-11" style="padding-right: 0;">
                        <table style="width: 100%;">
                            <tr>
                                <th>Pagare per:</br>To be paid by:</th>
                                <th colspan="2">Venditore</br>Senders</th>
                                <th colspan="2">Valuta</br>Currency</th>
                                <th>Destinatario</br>Consignee</th>
                            </tr>
                            <tr>
                                <td>Prezzo del trasporto:</br>Carriage chargers:</br>Descuentos:</br>Deductions:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Contante:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplementi:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplem. charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Spese accessorie:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Other charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>TOTAL</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 21/15 -->
        <div class="row" style="border-left: 1px solid black; border-right:1px solid black;">
            <div class="col-md-5" style="height: 100%; border-right: 1px solid black; border-top: 1px solid black">
                <div class="row">
                    <div class="col-md-1 number">21</div>
                    <div class="col-md-6">
                        <p>Stabilito in:</br>Estabé à</br>Established in</p>
                    </div>
                    <div class="col-md-1">
                        <p>il</br>le</br>on</p>
                    </div>
                    <div class="col-md-3">
                        <p class="insert"><b><?php echo $doc['documenti_contabilita_data_ritiro_merce']; //date('d/m/Y', strtotime($doc['documenti_contabilita_data_ritiro_merce'])); 
                                                ?></b></p>
                    </div>
                </div>
                <div class="row">
                    <fiv class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GEMONA (UD), IT</b></p>
                    </fiv>
                </div>
            </div>
            <div class="col-md-7" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">15</div>
                    <div class="col-md-11">
                        <p>Rimborso / Remboursement / Cash on delivery</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- 22/23/24 -->
        <!--<div class="row row-eq-height" style="background-color: cornflowerblue;">-->
        <div class="row" style="border: 1px solid #000;">
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">22</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del mittente</br>Signature et timbre de l'expéditeur</br>Signature and
                            stamp
                            of the sender</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">

                        <p class="insert"></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">23</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del Trasportatore</br>Signature et timbre du transporteur</br> Signature
                            and
                            stamp of
                            the carrier</p>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p class="insert"><?php echo $doc['documenti_contabilita_targhe']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">24</div>
                    <div class="col-md-11">
                        <p>Ricevuta della merce / Marcharndises recues / 24 Goods received</p>
                        <div class="row">
                            <div class="col-md-6">
                                <p>Luogo</br>Lieu</br>Place</p>
                            </div>
                            <div class="col-md-6">
                                <p>il</br>le</br>on</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p>Firma e Timbro del Destinatario</br>Signature et timbre du
                                    destinataire</br>Signature
                                    and stamp of the consignee</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end container-->
    </div>

    <!-- NEW PAGE -->

    <div class="container-fluid new_page">
        <div class="row">
            <div class="col-md-1 number">
                2
            </div>
            <div class="col-md-6">
                <p>Esemplare per il Destinatario - Exemplaire du Destinataire - Copy for Consignee</p>
            </div>
            <div class="col-md-5">
                <p class="text-right"><b>SPECIMEN</b></p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">1</div>
                    <div class="col-md-11">
                        <p>Mittente (nome, domicilio, paese)</br>Expediteur (nom, adresse, pays)</br>Sender (name,
                            addresse,
                            country)</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GRUPPO FRANCESCHINO LORIS s.r.l</b></br>VIA TRASAGHIS, 180</br>33103 -
                            GEMONA
                            (UD)</br>ITALY</br>TEL. +39 0432 981167</p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-6">
                        <p>Lettera di vettura internazionale</br>Lettre de voiture internacionale</br>International
                            consignment note</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>Questo trasporto è sottoposto,</br>nonostante tutte le clausole contrarie,</br>alla
                            Convenzione
                            del Trasp. Stradale</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p class="insert"><?php echo $doc['documenti_contabilita_tracking_code'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <!-- CMR -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p>Ce transport est soumis à la</br>Convention relative au contrat</br>de transport
                            International
                            de</br>marchandises par route.</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>This carriage is subjecte to the</br>convention on the contract for the</br>Inter. Carriage
                            of
                            goods by road.</p>
                    </div>
                </div>
            </div>
        </div>

        <!--2/16-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">2</div>
                    <div class="col-md-11">
                        <p>Destinatario (nome, domicilio, paese)</br>Destinataire (nom, adresse, pays)</br>Consignee
                            (name,
                            addresse, country)</p>
                        <p class="insert"><b><?php echo $destinatario['ragione_sociale'] ?></b></br><?php echo $destinatario['indirizzo'] ?>
                            </br><?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></br><?php echo strtoupper($destinatario['nazione']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-bottom: 0;">
                <div class="row">
                    <div class="col-md-1 number">16</div>
                    <div class="col-md-11">
                        <p>Trasportatore (nome, domicilio, paese)</br>Transporteur (nom, adresse, pays)</br>Carrier
                            (name,
                            addresse, country)</p>
                        <p class="insert"><?php echo $doc['documenti_contabilita_trasporto_a_cura_di'] ?></br><?php echo $doc['documenti_contabilita_vettori_residenza_domicilio'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!--3/17-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">3</div>
                    <div class="col-md-11">
                        <p>Luogo di consegna delle merci</br>Lieu pour la livraison de la marchandise</br>Place of
                            delivery
                            of
                            the goods</p>
                        <?php if (!empty($doc['documenti_contabilita_luogo_destinazione']) && $doc['documenti_contabilita_luogo_destinazione'] !== 'MEDESIMO') : ?>
                            <p class="insert"><b><?php echo $doc['documenti_contabilita_luogo_destinazione'] ?></b></p>
                        <?php else : ?>
                            <p class="insert"><b><?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></b></br><?php echo $destinatario['nazione'] ?></br>(come sopra/as above)</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-top: 1px solid black !important">
                <div class="row">
                    <div class="col-md-1 number">17</div>
                    <div class="col-md-11">
                        <p>Trasportatore successivo (nome, domicilio, paese)</br>Transporteur succesives (nom, adresse,
                            pays)</br>Successive carriers (name,
                            addresse, country)</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">4</div>
                    <div class="col-md-11">
                        <p>Luogo di presa in consegna delle merci</br>Lieu de la prise en charge de la
                            marchandise</br>Place
                            of taking over the goods</p>
                        <p class="insert"><b>33013 - GEMONA (UD)</b></br>ITALY</br>(come sopra/as above)</p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000;">
                    <div class="col-md-11 col-md-offset-1">
                        <p>Documenti allegati</br>Documents annexille</br>Documents attached</p>
                        <p class="insert"><?php echo "FATTURA / INVOICE {$doc['documenti_contabilita_numero']}-" . date('Y', strtotime($doc['documenti_contabilita_data_emissione'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border">
                <div class="row">
                    <div class="col-md-1 number">18</div>
                    <div class="col-md-11">
                        <p>Riserve ed osservazioni del corriere</br>Réserves ed observations du
                            transporteur</br>Carrer's
                            reservations and observations</p>
                        <p class="insert"><?php echo $doc['documenti_contabilita_annotazioni_trasporto'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0; border: 1px solid #000;">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 space_evenly">
                        <div class="number">6</div>
                        <p>Contrassegni e numeri</br>Marques et numéros</br>Marks and number</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">7</div>
                        <p>Numero dei colli</br>Nombre des colls</br>Number of packages</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">8</div>
                        <p>tipo di imballaggi</br>Mode d'emballage</br>Method of packin</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">9</div>
                        <p>Descrizione delle merci</br>Nature de la marchandise</br>Nature of the goods</p>
                    </div>
                </div>
                <div class="row" style="padding-bottom: 50px">
                    <div class="col-md-9 col-md-offset-3">
                        <p class="insert"><b>Tot. <?php echo $doc['documenti_contabilita_n_colli'] ?></b> <?php echo $doc['documenti_contabilita_descrizione_colli'] ?></p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000; border-right: 1px solid #000;">
                    <div class="col-md-3">
                        <p>Classe</br>Class</p>
                    </div>
                    <div class="col-md-3">
                        <p>Cifre</br>Number</p>
                    </div>
                    <div class="col-md-3">
                        <p>Lettere</br>Letter</p>
                    </div>
                    <div class="col-md-3">
                        <p>(ADR)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-left: 1px solid #000;">
                <div class="row">
                    <div class="col-md-4 space_between">
                        <div class="number">10</div>
                        <p>Numero statistico</br>N° statistique</br>N° statistic</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">11</div>
                        <p>Peso lordo Kg.</br>Polds brut Kg.</br>Gross Weight Kg.</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">12</div>
                        <p>Volume m3</br>Cubage m3</br>Volume in m3</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 content_center">
                        <p class="insert"></p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_peso'], 2, ',', ''); ?></p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_volume'], 2, ',', ''); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!--fine 4/18 row-->

        <!--13/19-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row" style="border-bottom: 1px solid #000">
                    <div class=" col-md-1 number">13</div>
                    <div class="col-md-11">
                        <p>Istruzioni del mittente</br>Instructions de l'expediteur</br>Sender's informations</p>
                        <p class="insert"><b><?php echo (isset($doc['clienti_referente']) && !empty($doc['clienti_referente'])) ? $doc['clienti_referente'] : ''; ?></b></br><?php echo (isset($doc['clienti_referente_telefono']) && !empty($doc['clienti_referente_telefono'])) ? 'TEL. ' . $doc['clienti_referente_telefono'] : ''; ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 number">14</div>
                    <div class="col-md-11">
                        <p>Tipo di pagamento</br>Presentactions d'affranchasement</br>Instructions as to payment carriage
                        </p>
                        <div class="row">
                            <div class="col-md-6">
                                <p><b>Porto franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (!empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-6">
                                <p><b>Porto non franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7 border" style="border-bottom: 0;">
                <div class="row">
                    <div class="col-md-1 number">19</div>
                    <div class="col-md-4">
                        <p>Convenzioni particolari</br>Conventions particulières</br>Special agreement</p>
                    </div>
                    <div class="col-md-7">
                        <p class="insert"></p>
                    </div>
                </div>
                <div class="row" style="border: 1px solid #000">
                    <div class="col-md-1 number">20</div>
                    <div class="col-md-11" style="padding-right: 0;">
                        <table style="width: 100%;">
                            <tr>
                                <th>Pagare per:</br>To be paid by:</th>
                                <th colspan="2">Venditore</br>Senders</th>
                                <th colspan="2">Valuta</br>Currency</th>
                                <th>Destinatario</br>Consignee</th>
                            </tr>
                            <tr>
                                <td>Prezzo del trasporto:</br>Carriage chargers:</br>Descuentos:</br>Deductions:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Contante:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplementi:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplem. charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Spese accessorie:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Other charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>TOTAL</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- 21/15 -->
        <div class="row">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">21</div>
                    <div class="col-md-6">
                        <p>Stabilito in:</br>Estabé à</br>Established in</p>
                    </div>
                    <div class="col-md-1">
                        <p>il</br>le</br>on</p>
                    </div>
                    <div class="col-md-3">
                        <p class="insert"><b><?php echo $doc['documenti_contabilita_data_ritiro_merce']; ?></b></p>
                    </div>
                </div>
                <div class="row">
                    <fiv class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GEMONA (UD), IT</b></p>
                    </fiv>
                </div>
            </div>
            <div class="col-md-7 border" style="border-top: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">15</div>
                    <div class="col-md-11">
                        <p>Rimborso / Remboursement / Cash on delivery</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- 22/23/24 -->
        <div class="row" style="border: 1px solid #000;">
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">22</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del mittente</br>Signature et timbre de l'expéditeur</br>Signature and
                            stamp
                            of the sender</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">

                        <p class="insert"></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">23</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del Trasportatore</br>Signature et timbre du transporteur</br> Signature
                            and
                            stamp of
                            the carrier</p>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p class="insert"><?php echo $doc['documenti_contabilita_targhe']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">24</div>
                    <div class="col-md-11">
                        <p>Ricevuta della merce / Marcharndises recues / 24 Goods received</p>
                        <div class="row">
                            <div class="col-md-6">
                                <p>Luogo</br>Lieu</br>Place</p>
                            </div>
                            <div class="col-md-6">
                                <p>il</br>le</br>on</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p>Firma e Timbro del Destinatario</br>Signature et timbre du destinataire</br>Signature
                                    and
                                    stamp of the consignee</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- NEW PAGE -->

    <div class="container-fluid new_page">
        <div class="row">
            <div class="col-md-1 number">
                <p>3</p>
            </div>
            <div class="col-md-6">
                <p>Esemplare per il Trasportatore - Exemplaire du Transporteur - Copy for Carrier</p>
            </div>
            <div class="col-md-5 text-right">
                <p><b>SPECIMEN</b></p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">1</div>
                    <div class="col-md-11">
                        <p>Mittente (nome, domicilio, paese)</br>Expediteur (nom, adresse, pays)</br>Sender (name, addresse,
                            country)</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GRUPPO FRANCESCHINO LORIS s.r.l</b></br>VIA TRASAGHIS, 180</br>33103 - GEMONA
                            (UD)</br>ITALY</br>TEL. +39 0432 981167</p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-6">
                        <p>Lettera di vettura internazionale</br>Lettre de voiture internacionale</br>International consignment note</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>Questo trasporto è sottoposto,</br>nonostante tutte le clausole contrarie,</br>alla Convenzione del Trasp. Stradale</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p class="insert"><?php echo $doc['documenti_contabilita_tracking_code'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <!-- CMR -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p>Ce transport est soumis à la</br>Convention relative au contrat</br>de transport International de</br>marchandises par route.</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>This carriage is subjecte to the</br>convention on the contract for the</br>Inter. Carriage of goods by road.</p>
                    </div>
                </div>
            </div>
        </div>

        <!--2/16-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">2</div>
                    <div class="col-md-11">
                        <p>Destinatario (nome, domicilio, paese)</br>Destinataire (nom, adresse, pays)</br>Consignee (name,
                            addresse, country)</p>
                        <p class="insert"><b><?php echo $destinatario['ragione_sociale'] ?></b></br><?php echo $destinatario['indirizzo'] ?>
                            </br><?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></br><?php echo strtoupper($destinatario['nazione']) ?>
                        </p>
                    </div>

                </div>
            </div>
            <div class="col-md-7 border" style="border-bottom: 0;">
                <div class="row">
                    <div class="col-md-1 number">16</div>
                    <div class="col-md-11">
                        <p>Trasportatore (nome, domicilio, paese)</br>Transporteur (nom, adresse, pays)</br>Carrier (name,
                            addresse, country)</p>
                        <p class="insert"><?php echo $doc['documenti_contabilita_trasporto_a_cura_di'] ?></br><?php echo nl2br($doc['documenti_contabilita_vettori_residenza_domicilio']) ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!--3/17-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">3</div>
                    <div class="col-md-11">
                        <p>Luogo di consegna delle merci</br>Lieu pour la livraison de la marchandise</br>Place of delivery of
                            the goods</p>
                        <?php if (!empty($doc['documenti_contabilita_luogo_destinazione']) && $doc['documenti_contabilita_luogo_destinazione'] !== 'MEDESIMO') : ?>
                            <p class="insert"><b><?php echo $doc['documenti_contabilita_luogo_destinazione'] ?></b></p>
                        <?php else : ?>
                            <p class="insert"><b>
                                    <?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></b></br><?php echo $destinatario['nazione'] ?></br>(come sopra/as above)</p>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-top: 0">
                <div class="row">
                    <div class="col-md-1 number">17</div>
                    <div class="col-md-11">
                        <p>Trasportatore successivo (nome, domicilio, paese)</br>Transporteur succesives (nom, adresse, pays)</br>Successive carriers (name,
                            addresse, country)</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">4</div>
                    <div class="col-md-11">
                        <p>Luogo di presa in consegna delle merci</br>Lieu de la prise en charge de la marchandise</br>Place of taking over the goods</p>
                        <p class="insert"><b>33013 - GEMONA (UD)</b></br>ITALY</br>(come sopra/as above)</p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000;">
                    <div class="col-md-11 col-md-offset-1">
                        <p>Documenti allegati</br>Documents annexille</br>Documents attached</p>
                        <p class="insert"><?php echo "FATTURA / INVOICE {$doc['documenti_contabilita_numero']}-" . date('Y', strtotime($doc['documenti_contabilita_data_emissione'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border">
                <div class="row">
                    <div class="col-md-1 number">18</div>
                    <div class="col-md-11">
                        <p>Riserve ed osservazioni del corriere</br>Réserves ed observations du transporteur</br>Carrer's reservations and observations</p>
                        <?php echo $doc['documenti_contabilita_annotazioni_trasporto'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0; border: 1px solid #000;">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 space_evenly">
                        <div class="number">6</div>
                        <p>Contrassegni e numeri</br>Marques et numéros</br>Marks and number</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">7</div>
                        <p>Numero dei colli</br>Nombre des colls</br>Number of packages</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">8</div>
                        <p>tipo di imballaggi</br>Mode d'emballage</br>Method of packin</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">9</div>
                        <p>Descrizione delle merci</br>Nature de la marchandise</br>Nature of the goods</p>
                    </div>
                </div>
                <div class="row" style="padding-bottom: 50px">
                    <div class="col-md-9 col-md-offset-3">
                        <p class="insert"><b>Tot. <?php echo $doc['documenti_contabilita_n_colli'] ?></b> <?php echo $doc['documenti_contabilita_descrizione_colli'] ?></p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000; border-right: 1px solid #000;">
                    <div class="col-md-3">
                        <p>Classe</br>Class</p>
                    </div>
                    <div class="col-md-3">
                        <p>Cifre</br>Number</p>
                    </div>
                    <div class="col-md-3">
                        <p>Lettere</br>Letter</p>
                    </div>
                    <div class="col-md-3">
                        <p>(ADR)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-left: 1px solid #000;">
                <div class="row">
                    <div class="col-md-4 space_between">
                        <div class="number">10</div>
                        <p>Numero statistico</br>N° statistique</br>N° statistic</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">11</div>
                        <p>Peso lordo Kg.</br>Polds brut Kg.</br>Gross Weight Kg.</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">12</div>
                        <p>Volume m3</br>Cubage m3</br>Volume in m3</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 content_center">
                        <p class="insert"></p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_peso'], 2, ',', ''); ?></p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_volume'], 2, ',', ''); ?></p>
                    </div>
                </div>

            </div>
        </div>
        <!--fine 4/18 row-->

        <!--13/19-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%; border-top: 0">
                <div class="row" style="border-bottom: 1px solid #000">
                    <div class=" col-md-1 number">13</div>
                    <div class="col-md-11">
                        <p>Istruzioni del mittente</br>Instructions de l'expediteur</br>Sender's informations</p>
                        <p class="insert"><b><?php echo (isset($doc['clienti_referente']) && !empty($doc['clienti_referente'])) ? $doc['clienti_referente'] : ''; ?></b></br><?php echo (isset($doc['clienti_referente_telefono']) && !empty($doc['clienti_referente_telefono'])) ? 'TEL. ' . $doc['clienti_referente_telefono'] : ''; ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 number">14</div>
                    <div class="col-md-11">
                        <p>Tipo di pagamento</br>Presentactions d'affranchasement</br>Instructions as to payment carriage</p>
                        <div class="row">
                            <div class="col-md-6">
                                <p><b>Porto franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (!empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                                <!--<div class="square"></div>-->
                            </div>
                        </div>
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-6">
                                <p><b>Porto non franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                                <!--<div class="square"></div>-->
                            </div>
                        </div>
                        <!--<p><b>Porto franco</b> <span class="square"></span></p>
                    <p><b>Porto non franco</b> <span class="square"></span></p>-->
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-bottom: 0; border-top:0; border-left: 0">
                <div class="row">
                    <div class="col-md-1 number">19</div>
                    <div class="col-md-4">
                        <p>Convenzioni particolari</br>Conventions particulières</br>Special agreement</p>
                        <!--<p class="insert"></p>-->
                    </div>
                    <div class="col-md-7">
                        <p class="insert"></p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000">
                    <div class="col-md-1 number">20</div>
                    <div class="col-md-11" style="padding-right: 0;">
                        <table style="width: 100%;">
                            <tr>
                                <th>Pagare per:</br>To be paid by:</th>
                                <th colspan="2">Venditore</br>Senders</th>
                                <th colspan="2">Valuta</br>Currency</th>
                                <th>Destinatario</br>Consignee</th>
                            </tr>
                            <tr>
                                <td>Prezzo del trasporto:</br>Carriage chargers:</br>Descuentos:</br>Deductions:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Contante:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplementi:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplem. charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Spese accessorie:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Other charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>TOTAL</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 21/15 -->
        <div class="row">
            <div class="col-md-5" style="height: 100%; border-top: 1px solid #000">
                <div class="row">
                    <div class="col-md-1 number">21</div>
                    <div class="col-md-6">
                        <p>Stabilito in:</br>Estabé à</br>Established in</p>
                    </div>
                    <div class="col-md-1">
                        <p>il</br>le</br>on</p>
                    </div>
                    <div class="col-md-3">
                        <p class="insert"><b><?php echo $doc['documenti_contabilita_data_ritiro_merce']; ?></b></p>
                    </div>
                </div>
                <div class="row">
                    <fiv class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GEMONA (UD), IT</b></p>
                    </fiv>
                </div>
            </div>
            <div class="col-md-7" style="border: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">15</div>
                    <div class="col-md-11">
                        <p>Rimborso / Remboursement / Cash on delivery</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- 22/23/24 -->
        <!--<div class="row row-eq-height" style="background-color: cornflowerblue;">-->
        <div class="row" style="border: 1px solid #000;">
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">22</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del mittente</br>Signature et timbre de l'expéditeur</br>Signature and
                            stamp
                            of the sender</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">23</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del Trasportatore</br>Signature et timbre du transporteur</br> Signature
                            and
                            stamp of
                            the carrier</p>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p class="insert"><?php echo $doc['documenti_contabilita_targhe']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">24</div>
                    <div class="col-md-11">
                        <p>Ricevuta della merce / Marcharndises recues / 24 Goods received</p>
                        <div class="row">
                            <div class="col-md-6">
                                <p>Luogo</br>Lieu</br>Place</p>
                            </div>
                            <div class="col-md-6">
                                <p>il</br>le</br>on</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p>Firma e Timbro del Destinatario</br>Signature et timbre du destinataire</br>Signature and stamp of the consignee</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end container-->
    </div>

    <!-- NEW PAGE -->

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-1 number">
                <p>4</p>
            </div>
            <div class="col-md-11 text-right">
                <p><b>SPECIMEN</b></p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">1</div>
                    <div class="col-md-11">
                        <p>Mittente (nome, domicilio, paese)</br>Expediteur (nom, adresse, pays)</br>Sender (name, addresse,
                            country)</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GRUPPO FRANCESCHINO LORIS s.r.l</b></br>VIA TRASAGHIS, 180</br>33103 - GEMONA
                            (UD)</br>ITALY</br>TEL. +39 0432 981167</p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-6">
                        <p>Lettera di vettura internazionale</br>Lettre de voiture internacionale</br>International consignment note</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>Questo trasporto è sottoposto,</br>nonostante tutte le clausole contrarie,</br>alla Convenzione del Trasp. Stradale</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p class="insert"><?php echo $doc['documenti_contabilita_tracking_code'] ?></p>
                    </div>
                    <div class="col-md-6">
                        <p></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p>Ce transport est soumis à la</br>Convention relative au contrat</br>de transport International de</br>marchandises par route.</p>
                    </div>
                    <div class="col-md-6 text-right">
                        <p>This carriage is subjecte to the</br>convention on the contract for the</br>Inter. Carriage of goods by road.</p>
                    </div>
                </div>
            </div>
        </div>

        <!--2/16-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">2</div>
                    <div class="col-md-11">
                        <p>Destinatario (nome, domicilio, paese)</br>Destinataire (nom, adresse, pays)</br>Consignee (name,
                            addresse, country)</p>
                        <p class="insert"><b><?php echo $destinatario['ragione_sociale'] ?></b></br><?php echo $destinatario['indirizzo'] ?>
                            </br><?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></br><?php echo strtoupper($destinatario['nazione']) ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-bottom: 0;">
                <div class="row">
                    <div class="col-md-1 number">16</div>
                    <div class="col-md-11">
                        <p>Trasportatore (nome, domicilio, paese)</br>Transporteur (nom, adresse, pays)</br>Carrier (name,
                            addresse, country)</p>
                        <p class="insert"><?php echo $doc['documenti_contabilita_trasporto_a_cura_di'] ?></br><?php echo $doc['documenti_contabilita_vettori_residenza_domicilio'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!--3/17-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">3</div>
                    <div class="col-md-11">
                        <p>Luogo di consegna delle merci</br>Lieu pour la livraison de la marchandise</br>Place of delivery of
                            the goods</p>
                        <?php if (!empty($doc['documenti_contabilita_luogo_destinazione']) && $doc['documenti_contabilita_luogo_destinazione'] !== 'MEDESIMO') : ?>
                            <p class="insert"><b><?php echo $doc['documenti_contabilita_luogo_destinazione'] ?></b></p>
                        <?php else : ?>
                            <p class="insert"><b><?php echo "{$destinatario['cap']} {$destinatario['citta']} {$destinatario['provincia']}" ?></b></br><?php echo $destinatario['nazione'] ?></br>(come sopra/as above)</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-top: 0">
                <div class="row">
                    <div class="col-md-1 number">17</div>
                    <div class="col-md-11">
                        <p>Trasportatore successivo (nome, domicilio, paese)</br>Transporteur succesives (nom, adresse, pays)</br>Successive carriers (name,
                            addresse, country)</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">4</div>
                    <div class="col-md-11">
                        <p>Luogo di presa in consegna delle merci</br>Lieu de la prise en charge de la marchandise</br>Place of taking over the goods</p>
                        <p class="insert"><b>33013 - GEMONA (UD)</b></br>ITALY</br>(come sopra/as above)</p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000;">
                    <div class="col-md-11 col-md-offset-1">
                        <p>Documenti allegati</br>Documents annexille</br>Documents attached</p>
                        <p class="insert"><?php echo "FATTURA / INVOICE {$doc['documenti_contabilita_numero']}-" . date('Y', strtotime($doc['documenti_contabilita_data_emissione'])); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border">
                <div class="row">
                    <div class="col-md-1 number">18</div>
                    <div class="col-md-11">
                        <p>Riserve ed osservazioni del corriere</br>Réserves ed observations du transporteur</br>Carrer's reservations and observations</p>
                        <p class="insert">
                            <?php echo $doc['documenti_contabilita_annotazioni_trasporto'] ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!--4/18-->
        <div class="row" style=" padding-top: 0; border: 1px solid #000;">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-md-3 space_evenly">
                        <div class="number">6</div>
                        <p>Contrassegni e numeri</br>Marques et numéros</br>Marks and number</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">7</div>
                        <p>Numero dei colli</br>Nombre des colls</br>Number of packages</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">8</div>
                        <p>tipo di imballaggi</br>Mode d'emballage</br>Method of packin</p>
                    </div>
                    <div class="col-md-3 space_evenly">
                        <div class="number">9</div>
                        <p>Descrizione delle merci</br>Nature de la marchandise</br>Nature of the goods</p>
                    </div>
                </div>
                <div class="row" style="padding-bottom: 50px">
                    <div class="col-md-9 col-md-offset-3">
                        <p class="insert"><b>Tot. <?php echo $doc['documenti_contabilita_n_colli'] ?></b> <?php echo $doc['documenti_contabilita_descrizione_colli'] ?></p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000; border-right: 1px solid #000;">
                    <div class="col-md-3">
                        <p>Classe</br>Class</p>
                    </div>
                    <div class="col-md-3">
                        <p>Cifre</br>Number</p>
                    </div>
                    <div class="col-md-3">
                        <p>Lettere</br>Letter</p>
                    </div>
                    <div class="col-md-3">
                        <p>(ADR)</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-left: 1px solid #000;">
                <div class="row">
                    <div class="col-md-4 space_between">
                        <div class="number">10</div>
                        <p>Numero statistico</br>N° statistique</br>N° statistic</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">11</div>
                        <p>Peso lordo Kg.</br>Polds brut Kg.</br>Gross Weight Kg.</p>
                    </div>
                    <div class="col-md-4 space_between" style="border-left: 1px solid #000;">
                        <div class="number">12</div>
                        <p>Volume m3</br>Cubage m3</br>Volume in m3</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 content_center">
                        <p class="insert">-</p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_peso'], 2, ',', ''); ?></p>
                    </div>
                    <div class="col-md-4 content_center" style="border-left: 1px solid #000;">
                        <p class="insert">ca/approx</br><?php echo number_format($doc['documenti_contabilita_volume'], 2, ',', ''); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!--fine 4/18 row-->

        <!--13/19-->
        <div class="row" style=" padding-top: 0;">
            <div class="col-md-5 border" style="height: 100%; border-top: 0">
                <div class="row" style="border-bottom: 1px solid #000">
                    <div class=" col-md-1 number">13</div>
                    <div class="col-md-11">
                        <p>Istruzioni del mittente</br>Instructions de l'expediteur</br>Sender's informations</p>
                        <p class="insert"><b><?php echo (isset($doc['clienti_referente']) && !empty($doc['clienti_referente'])) ? $doc['clienti_referente'] : ''; ?></b></br><?php echo (isset($doc['clienti_referente_telefono']) && !empty($doc['clienti_referente_telefono'])) ? 'TEL. ' . $doc['clienti_referente_telefono'] : ''; ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-1 number">14</div>
                    <div class="col-md-11">
                        <p>Tipo di pagamento</br>Presentactions d'affranchasement</br>Instructions as to payment carriage</p>
                        <div class="row">
                            <div class="col-md-6">
                                <p><b>Porto franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (!empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        <div class="row" style="margin-top: 10px;">
                            <div class="col-md-6">
                                <p><b>Porto non franco</b>
                            </div>
                            <div class="col-md-6">
                                <input type="checkbox" <?php echo (empty($doc['documenti_contabilita_porto'])) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7 border" style="border-bottom: 0; border-top:0; border-left: 0">
                <div class="row">
                    <div class="col-md-1 number">19</div>
                    <div class="col-md-4">
                        <p>Convenzioni particolari</br>Conventions particulières</br>Special agreement</p>
                        <!--<p class="insert"></p>-->
                    </div>
                    <div class="col-md-7">
                        <p class="insert"></p>
                    </div>
                </div>
                <div class="row" style="border-top: 1px solid #000">
                    <div class="col-md-1 number">20</div>
                    <div class="col-md-11" style="padding-right: 0;">
                        <table style="width: 100%;">
                            <tr>
                                <th>Pagare per:</br>To be paid by:</th>
                                <th colspan="2">Venditore</br>Senders</th>
                                <th colspan="2">Valuta</br>Currency</th>
                                <th>Destinatario</br>Consignee</th>
                            </tr>
                            <tr>
                                <td>Prezzo del trasporto:</br>Carriage chargers:</br>Descuentos:</br>Deductions:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Contante:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplementi:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Supplem. charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Spese accessorie:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>Other charges:</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>TOTAL</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>



        </div>

        <!-- 21/15 -->
        <div class="row">
            <div class="col-md-5" style="height: 100%; border-top: 1px solid #000">
                <div class="row">
                    <div class="col-md-1 number">21</div>
                    <div class="col-md-6">
                        <p>Stabilito in:</br>Estabé à</br>Established in</p>
                    </div>
                    <div class="col-md-1">
                        <p>il</br>le</br>on</p>
                    </div>
                    <div class="col-md-3">
                        <p class="insert"><b><?php echo $doc['documenti_contabilita_data_ritiro_merce']; ?></b></p>
                    </div>
                </div>
                <div class="row">
                    <fiv class="col-md-11 col-md-offset-1">
                        <p class="insert"><b>GEMONA (UD), IT</b></p>
                    </fiv>
                </div>
            </div>
            <div class="col-md-7" style="border: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">15</div>
                    <div class="col-md-11">
                        <p>Rimborso / Remboursement / Cash on delivery</p>
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
        </div>


        <!-- 22/23/24 -->
        <!--<div class="row row-eq-height" style="background-color: cornflowerblue;">-->
        <div class="row" style="border: 1px solid #000;">
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">22</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del mittente</br>Signature et timbre de l'expéditeur</br>Signature and
                            stamp
                            of the sender</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p class="insert"></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="border-right: 1px solid #000; height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">23</div>
                    <div class="col-md-11">
                        <p>Firma e Timbro del Trasportatore</br>Signature et timbre du transporteur</br> Signature
                            and
                            stamp of
                            the carrier</p>

                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <p class="insert"><?php echo $doc['documenti_contabilita_targhe']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4" style="height: 100%;">
                <div class="row">
                    <div class="col-md-1 number">24</div>
                    <div class="col-md-11">
                        <p>Ricevuta della merce / Marcharndises recues / 24 Goods received</p>
                        <div class="row">
                            <div class="col-md-6">
                                <p>Luogo</br>Lieu</br>Place</p>
                            </div>
                            <div class="col-md-6">
                                <p>il</br>le</br>on</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <p>Firma e Timbro del Destinatario</br>Signature et timbre du destinataire</br>Signature and stamp of the consignee</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>