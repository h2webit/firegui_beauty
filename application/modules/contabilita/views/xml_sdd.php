<?php $this->load->model('contabilita/docs'); ?><?php echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL; ?>
<CBISDDReqLogMsg xmlns="urn:CBI:xsd:CBISDDReqLogMsg.00.01.00">
    <GrpHdr>
        <MsgId><?php echo $sdd_id; ?></MsgId>
        <CreDtTm><?php echo $creation_datetime; ?></CreDtTm>
        <NbOfTxs><?php echo $number_of_transactions ?></NbOfTxs>
        <CtrlSum><?php echo $total; ?></CtrlSum>
        <InitgPty>
            <Nm><?php echo $azienda['documenti_contabilita_settings_company_name'] ?></Nm>
            <Id>
                <OrgId>
                    <Othr>
                        <Id><?php echo $azienda['documenti_contabilita_settings_cuc_code'] ?></Id>
                        <Issr>CBI</Issr>
                    </Othr>
                </OrgId>
            </Id>
        </InitgPty>
    </GrpHdr>
    <PmtInf>
        <PmtInfId><?php echo date('dmyHis'); ?></PmtInfId>
        <PmtMtd>DD</PmtMtd>
        <BtchBookg>false</BtchBookg>
        <PmtTpInf>
            <SvcLvl>
                <Cd>SEPA</Cd>
            </SvcLvl>
            <LclInstrm>
                <Cd>CORE</Cd>
            </LclInstrm>
            <SeqTp>RCUR</SeqTp>
        </PmtTpInf>
        <ReqdColltnDt><?php echo (new DateTime)->modify('+2days')->format('Y-m-d') ?></ReqdColltnDt>
        <Cdtr>
            <Nm><?php echo $azienda['documenti_contabilita_settings_company_name'] ?></Nm>
            <PstlAdr>
                <StrtNm><?php echo $azienda['documenti_contabilita_settings_company_address'] ?></StrtNm>
                <PstCd><?php echo $azienda['documenti_contabilita_settings_company_zipcode'] ?></PstCd>
                <TwnNm><?php echo $azienda['documenti_contabilita_settings_company_city'] ?></TwnNm>
                <CtrySubDvsn><?php echo $azienda['documenti_contabilita_settings_company_province'] ?></CtrySubDvsn>
                <Ctry><?php echo strtoupper(substr($azienda['documenti_contabilita_settings_company_country'], 0, 2)); ?></Ctry>
            </PstlAdr>
        </Cdtr>
        <CdtrAcct>
            <Id>
                <IBAN><?php echo trim($conto['conti_correnti_iban']); ?></IBAN>
            </Id>
        </CdtrAcct>
        <CdtrAgt>
            <FinInstnId>
                <ClrSysMmbId>
                    <MmbId><?php echo $this->docs->extractIbanData($conto['conti_correnti_iban'])['abi']; ?></MmbId>
                </ClrSysMmbId>
            </FinInstnId>
        </CdtrAgt>
        <ChrgBr>SLEV</ChrgBr>
        <CdtrSchmeId>
            <Id>
                <PrvtId>
                    <Othr>
                        <Id><?php echo trim($azienda['documenti_contabilita_settings_cid']); ?></Id>
                        <SchmeNm>
                            <Prtry>SEPA</Prtry>
                        </SchmeNm>
                    </Othr>
                </PrvtId>
            </Id>
        </CdtrSchmeId>
        <?php foreach ($documenti as $doc) : $dest = json_decode($doc['documenti_contabilita_destinatario'], true);
            //Prendo il conto rid default
            $conto = $this->apilib->searchFirst('customers_bank_accounts', [
                'customers_bank_accounts_default' => DB_BOOL_TRUE,
                'customers_bank_accounts_customer_id' => $doc['documenti_contabilita_customer_id'],
                "customers_bank_accounts_id IN (SELECT customers_bank_accounts_id FROM customers_banks_types WHERE bank_types_id = 2)" //Rid
            ]);


            //Se non c'è prendo il conto rid
            if (!$conto) {
                $conto = $this->apilib->searchFirst('customers_bank_accounts', [
                    //'customers_bank_accounts_default' => DB_BOOL_TRUE,
                    'customers_bank_accounts_customer_id' => $doc['documenti_contabilita_customer_id'],
                    "customers_bank_accounts_id IN (SELECT customers_bank_accounts_id FROM customers_banks_types WHERE bank_types_id = 2)" //Rid
                ]);
            }
            //Se non c'è prendo il conto default
            if (!$conto) {
                $conto = $this->apilib->searchFirst('customers_bank_accounts', [
                    'customers_bank_accounts_default' => DB_BOOL_TRUE,
                    'customers_bank_accounts_customer_id' => $doc['documenti_contabilita_customer_id'],
                    //"customers_bank_accounts_id IN (SELECT customers_bank_accounts_id FROM customers_banks_types WHERE bank_types_id = 2)" //Rid
                ]);
            }
            //Se non c'è prendo il first
            if (!$conto) {
                $conto = $this->apilib->searchFirst('customers_bank_accounts', [
                    //'customers_bank_accounts_default' => DB_BOOL_TRUE,
                    'customers_bank_accounts_customer_id' => $doc['documenti_contabilita_customer_id'],
                    //"customers_bank_accounts_id IN (SELECT customers_bank_accounts_id FROM customers_banks_types WHERE bank_types_id = 2)" //Rid
                ]);
            }
            //Se non c'è, errore...
            if (!$conto) {
                die("Non trovo il conto per il cliente '{$dest['ragione_sociale']}'");
            }

        ?>
            <DrctDbtTxInf>
                <PmtId>
                    <InstrId><?php echo $doc['documenti_contabilita_scadenze_id'] ?></InstrId>
                    <EndToEndId><?php echo $doc['documenti_contabilita_scadenze_id'] ?></EndToEndId>
                </PmtId>
                <InstdAmt Ccy="EUR"><?php echo number_format($doc['documenti_contabilita_scadenze_ammontare'], 2, '.', '') ?></InstdAmt>
                <ChrgBr>SLEV</ChrgBr>
                <DrctDbtTx>
                    <MndtRltdInf>
                        <MndtId><?php echo substr($dest['ragione_sociale'], 0, 3); ?><?php echo substr($conto['customers_bank_accounts_iban'], 0, -5); ?></MndtId>
                        <DtOfSgntr><?php echo (new DateTime($doc['documenti_contabilita_data_emissione']))->format('Y-m-d') ?></DtOfSgntr>
                    </MndtRltdInf>
                </DrctDbtTx>
                <Dbtr>
                    <Nm><?php echo $dest['ragione_sociale'] ?></Nm>
                    <PstlAdr>
                        <StrtNm><?php echo $dest['indirizzo'] ?></StrtNm>
                        <PstCd><?php echo $dest['cap'] ?></PstCd>
                        <TwnNm><?php echo $dest['citta'] ?></TwnNm>
                        <CtrySubDvsn><?php echo $dest['provincia'] ?></CtrySubDvsn>
                        <Ctry><?php echo strtoupper(substr($dest['nazione'], 0, 2)); ?></Ctry>
                    </PstlAdr>
                    <?php /*<Id>
                        <OrgId>
                            <Othr>
                                <Id>04247910286</Id>
                                <Issr>ADE</Issr>
                            </Othr>
                        </OrgId>
                    </Id> */ ?>
                </Dbtr>
                <DbtrAcct>
                    <Id>
                        <IBAN><?php echo $conto['customers_bank_accounts_iban'] ?? null; ?></IBAN>
                    </Id>
                </DbtrAcct>
                <Purp>
                    <Cd>PADD</Cd>
                </Purp>
                <RmtInf>
                    <Ustrd>Fatt. <?php echo $doc['documenti_contabilita_numero'] ?> del <?php echo dateFormat($doc['documenti_contabilita_data_emissione']); ?></Ustrd>
                </RmtInf>
            </DrctDbtTxInf>
        <?php endforeach;  ?>
    </PmtInf>
</CBISDDReqLogMsg>