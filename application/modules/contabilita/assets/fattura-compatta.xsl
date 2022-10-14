<?xml version="1.0"?>
<xsl:stylesheet
	version="1.1"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:b="http://www.fatturapa.gov.it/sdi/fatturapa/v1.1"
	xmlns:c="http://www.fatturapa.gov.it/sdi/fatturapa/v1.0"
	xmlns:a="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2"
	xmlns:d="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.0">

  <xsl:output method="html" />
  <xsl:decimal-format name="euro" decimal-separator="," grouping-separator="."/>
	
	<xsl:template name="FormatDateRicezione">
    <xsl:param name="DateTime" />
		
    <xsl:variable name="year" select="substring($DateTime,7,2)" />
    <xsl:variable name="month" select="substring($DateTime,4,2)" />
    <xsl:variable name="day" select="substring($DateTime,1,2)" />

    <xsl:value-of select="$day" />
    <xsl:value-of select="'/'" />
    <xsl:value-of select="$month" />
    <xsl:value-of select="'/'" />
    <xsl:value-of select="$year" />

  </xsl:template>

  <xsl:template name="FormatDateIta">
    <xsl:param name="DateTime" />

    <xsl:variable name="year" select="substring($DateTime,1,4)" />
    <xsl:variable name="month" select="substring($DateTime,6,2)" />
    <xsl:variable name="day" select="substring($DateTime,9,2)" />

    <xsl:value-of select="$day" />
    <xsl:value-of select="'-'" />
    <xsl:value-of select="$month" />
    <xsl:value-of select="'-'" />
    <xsl:value-of select="$year" />

  </xsl:template>

  <xsl:template name="FormatIVA">
    <xsl:param name="Natura" />
    <xsl:param name="IVA" />
    <xsl:choose>
      <xsl:when test="$Natura">
        <xsl:value-of select="$Natura" />
      </xsl:when>
      <xsl:otherwise>
        <xsl:if test="$IVA">
          <xsl:value-of select="format-number($IVA,  '###.###.##0,00', 'euro')" />
        </xsl:if>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="FormatSconto">
    <xsl:param name="tipo" />
    <xsl:param name="percentuale" />
    <xsl:param name="importo" />

    <xsl:choose>
      <xsl:when test="$tipo = 'SC' ">
        <xsl:text>-</xsl:text>
      </xsl:when>
      <xsl:when test="$tipo = 'MG'">
        <xsl:text>+</xsl:text>

      </xsl:when>
      <xsl:otherwise>

      </xsl:otherwise>
    </xsl:choose>


    <xsl:choose>
      <xsl:when test="$percentuale">
        <xsl:value-of select="$percentuale" />
        <xsl:text>%</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:if test="$importo">
          <xsl:value-of select="format-number($importo,  '###.###.##0,00######', 'euro')" />
        </xsl:if>
      </xsl:otherwise>
    </xsl:choose>
    <xsl:text> </xsl:text>

  </xsl:template>

  <xsl:template name="FormatColSconto">
    <xsl:param name="tipo" />
    <xsl:param name="percentuale" />
    <xsl:param name="importo" />

    <xsl:choose>
      <xsl:when test="$tipo = 'SC' ">
        <xsl:text>-</xsl:text>
      </xsl:when>
      <xsl:when test="$tipo = 'MG'">
        <xsl:text>+</xsl:text>

      </xsl:when>
      <xsl:otherwise>

      </xsl:otherwise>
    </xsl:choose>


    <xsl:choose>
      <xsl:when test="$percentuale">
        <xsl:value-of select="$percentuale" />
        <xsl:text>%</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:if test="$importo">
          <xsl:value-of select="format-number($importo,  '###.###.##0,00######', 'euro')" />
        </xsl:if>
      </xsl:otherwise>
    </xsl:choose>

  </xsl:template>

  <!--DatiOrdineAcquisto  Vs.Ord. XXXXXX del 26/09/2018 CUP:YYYYYY CIG:ZZZZZZZ-->
  <!--DatiContratto  Contratto XXXXXX del 26/09/2018 CUP:YYYYYY CIG:ZZZZZZZ -->
  <!--DatiConvenzione  Convenzione XXXXXX del 26/09/2018 CUP:YYYYYY CIG:ZZZZZZZ -->
  <!--DatiRicezione  Ricezione XXXXXX del 26/09/2018 CUP:YYYYYY CIG:ZZZZZZZ -->
  <!--Fatture collegate Fatt.coll. XXXXXX del 26/09/2018 CUP:YYYYYY CIG:ZZZZZZZ -->
  <xsl:template name="DatiCorrelati">
    <xsl:param name="Prefix" />
    <xsl:param name="IdDocumento" />
    <xsl:param name="Data" />
    <xsl:param name="CodiceCUP" />
    <xsl:param name="CodiceCIG" />
		
		<xsl:param name="NumItem" />
		<xsl:param name="CodiceCommessaConvenzione" />
		
    <xsl:variable name="descrizione" >
      <xsl:value-of select="$Prefix" />
      <xsl:value-of select="$IdDocumento" />
      <xsl:if test="$Data">
        <xsl:text> del </xsl:text>
        <xsl:call-template name="FormatDateIta">
          <xsl:with-param name="DateTime" select="$Data" />
        </xsl:call-template>
      </xsl:if>
      <xsl:if test="$CodiceCUP">
        <xsl:text> CUP: </xsl:text>
        <xsl:value-of select="$CodiceCUP" />
      </xsl:if>
      <xsl:if test="$CodiceCIG">
        <xsl:text> CIG: </xsl:text>
        <xsl:value-of select="$CodiceCIG" />
      </xsl:if>
			
			<xsl:if test="$NumItem">
        <xsl:text> Num. Linea: </xsl:text>
        <xsl:value-of select="$NumItem" />
      </xsl:if>
			<xsl:if test="$CodiceCommessaConvenzione">
        <xsl:text> Cod.Commessa: </xsl:text>
        <xsl:value-of select="$CodiceCommessaConvenzione" />
      </xsl:if>
			
    </xsl:variable>
    <xsl:if test="$descrizione">
      <xsl:call-template name="AltraDescrizioneLinea">
        <xsl:with-param name="textDescrizione" select = "$descrizione" />
      </xsl:call-template>
    </xsl:if>
  </xsl:template>

  <xsl:template match="DatiDDT"> 
	  <xsl:variable name="descri_DAO" >
		
		  <xsl:text>DDT </xsl:text>
		  <xsl:value-of select="NumeroDDT" />
		  <xsl:if test="DataDDT">
			<xsl:text> del </xsl:text>
			<xsl:call-template name="FormatDateIta">
			  <xsl:with-param name="DateTime" select="DataDDT" />
			</xsl:call-template>
		  </xsl:if>
	  
	  </xsl:variable>

	  <xsl:if test="$descri_DAO">
		<xsl:call-template name="AltraDescrizioneLinea">
		  <xsl:with-param name="textDescrizione" select = "$descri_DAO" />
		</xsl:call-template>
	  </xsl:if>
  </xsl:template>
    
  <xsl:template match="DettaglioLinee">
    <xsl:param name="r" />
    <xsl:param name="posASWRELSTD" />
    <xsl:param name="TipoFattura" />
    <xsl:param name="IndiceBody" />

    <!--Numero Linea -->
    <xsl:variable name="valNumeroLinea" >
      <xsl:value-of select="NumeroLinea" />
    </xsl:variable>
   
    <!--Pre LINEA OpzPreLineaDatiDDT -->

    <xsl:choose>

      <xsl:when test="OpzPreLineaDatiDDT">
        <!--Pre LINEA OpzPreLineaDatiDDT -->
        <xsl:for-each select="OpzPreLineaDatiDDT"  >
          <xsl:call-template name="AltraDescrizioneLinea">
            <xsl:with-param name="textDescrizione" select = "." />
          </xsl:call-template>
        </xsl:for-each>
        
      </xsl:when>
	    <xsl:otherwise>	 

	   <xsl:for-each select="$TipoFattura/FatturaElettronicaBody[$IndiceBody]/DatiGenerali/DatiDDT[ number(./RiferimentoNumeroLinea) = $valNumeroLinea] ">		
				<xsl:apply-templates select="."/>	<!-- apply DatiDDT template -->
        </xsl:for-each>
     
      </xsl:otherwise>
    </xsl:choose>


    <!--DatiOrdineAcquisto  -->
    <xsl:choose>
      <!--Pre LINEA OpzPreLineaDatiOrdineAcquisto  -->
      <xsl:when test="OpzPreLineaDatiOrdineAcquisto ">
        <xsl:for-each select="OpzPreLineaDatiOrdineAcquisto"  >
          <xsl:call-template name="AltraDescrizioneLinea">
            <xsl:with-param name="textDescrizione" select = "." />
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>

      <xsl:otherwise>
	  
	  <xsl:for-each select="$TipoFattura/FatturaElettronicaBody[$IndiceBody]/DatiGenerali/DatiOrdineAcquisto[ number(./RiferimentoNumeroLinea) = $valNumeroLinea] ">		
          <xsl:call-template name="DatiCorrelati" >
            <xsl:with-param name="Prefix"   select='"Vs.Ord. "'/>
            <xsl:with-param name="IdDocumento" select="IdDocumento"/>
            <xsl:with-param name="Data" select="Data"/>
            <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
            <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
						
            <xsl:with-param name="NumItem" select="NumItem"/>
            <xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
						
          </xsl:call-template >
        </xsl:for-each>
	  
	  
	  </xsl:otherwise>
    </xsl:choose>

    <!--DatiContratto  -->
      <xsl:choose>
      <!--Pre LINEA OpzPreLineaDatiContratto  -->
      <xsl:when test="OpzPreLineaDatiContratto ">
        <xsl:for-each select="OpzPreLineaDatiContratto"  >
          <xsl:call-template name="AltraDescrizioneLinea">
            <xsl:with-param name="textDescrizione" select = "." />
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
     
      <xsl:otherwise>
		<xsl:for-each select="$TipoFattura/FatturaElettronicaBody[$IndiceBody]/DatiGenerali/DatiContratto[ number(./RiferimentoNumeroLinea) = $valNumeroLinea] ">		
          <xsl:call-template name="DatiCorrelati" >
            <xsl:with-param name="Prefix"  select='"Contratto "'/>
            <xsl:with-param name="IdDocumento" select="IdDocumento"/>
            <xsl:with-param name="Data" select="Data"/>
            <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
            <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
						
						<xsl:with-param name="NumItem" select="NumItem"/>
            <xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
						
          </xsl:call-template >
        </xsl:for-each>
	  
	  </xsl:otherwise>
    </xsl:choose>

    <!--DatiConvenzione -->
    <xsl:choose>
      <!--Pre LINEA OpzPreLineaDatiConvenzione -->
      <xsl:when test="OpzPreLineaDatiConvenzione ">
        <xsl:for-each select="OpzPreLineaDatiConvenzione"  >
          <xsl:call-template name="AltraDescrizioneLinea">
            <xsl:with-param name="textDescrizione" select = "." />
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
     
      <xsl:otherwise>
	  
	  <xsl:for-each select="$TipoFattura/FatturaElettronicaBody[$IndiceBody]/DatiGenerali/DatiConvenzione[ number(./RiferimentoNumeroLinea) = $valNumeroLinea] ">		
          <xsl:call-template name="DatiCorrelati" >
            <xsl:with-param name="Prefix"  select='"Convenzione "'/>
            <xsl:with-param name="IdDocumento" select="IdDocumento"/>
            <xsl:with-param name="Data" select="Data"/>
            <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
            <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
						
						<xsl:with-param name="NumItem" select="NumItem"/>
            <xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
						
          </xsl:call-template >
        </xsl:for-each>
	  
	  </xsl:otherwise>
    </xsl:choose>

    <!--DatiRicezione -->
    <xsl:choose>
      <!--Pre LINEA OpzPreLineaDatiRicezione -->
      <xsl:when test="OpzPreLineaDatiRicezione ">
        <xsl:for-each select="OpzPreLineaDatiRicezione"  >
          <xsl:call-template name="AltraDescrizioneLinea">
            <xsl:with-param name="textDescrizione" select = "." />
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
     
      <xsl:otherwise>
	  
	 <xsl:for-each select="$TipoFattura/FatturaElettronicaBody[$IndiceBody]/DatiGenerali/DatiRicezione[ number(./RiferimentoNumeroLinea) = $valNumeroLinea] ">
          <xsl:call-template name="DatiCorrelati" >
            <xsl:with-param name="Prefix"  select='"Ricezione "'/>
            <xsl:with-param name="IdDocumento" select="IdDocumento"/>
            <xsl:with-param name="Data" select="Data"/>
            <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
            <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
						
						<xsl:with-param name="NumItem" select="NumItem"/>
            <xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
						
          </xsl:call-template >
        </xsl:for-each>
	  
	  </xsl:otherwise>
    </xsl:choose>

    <!--DatiFattureCollegate-->
    <xsl:choose>
      <!--Pre LINEA OpzPreLineaDatiFattureCollegate-->
      <xsl:when test="OpzPreLineaDatiFattureCollegate ">
        <xsl:for-each select="OpzPreLineaDatiFattureCollegate"  >
          <xsl:call-template name="AltraDescrizioneLinea">
            <xsl:with-param name="textDescrizione" select = "." />
          </xsl:call-template>
        </xsl:for-each>
      </xsl:when>
      <xsl:otherwise>
	  
     <xsl:for-each select="$TipoFattura/FatturaElettronicaBody[$IndiceBody]/DatiGenerali/DatiFattureCollegate[ number(./RiferimentoNumeroLinea) = $valNumeroLinea] ">
          <xsl:call-template name="DatiCorrelati" >
            <xsl:with-param name="Prefix"  select='"Fatt.coll. "'/>
            <xsl:with-param name="IdDocumento" select="IdDocumento"/>
            <xsl:with-param name="Data" select="Data"/>
            <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
            <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
						
						<xsl:with-param name="NumItem" select="NumItem"/>
            <xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
						
          </xsl:call-template >
        </xsl:for-each>
	  
	  </xsl:otherwise>
    </xsl:choose>

    <!--DETTAGLIO LINEE -->

    <xsl:choose>
      <xsl:when test="$posASWRELSTD = $r">
        <xsl:call-template name="DettaglioLineeASW"/>
      </xsl:when>

      <xsl:otherwise>

        <tr>
          <td>
            <xsl:for-each select="CodiceArticolo"  >
              <div class="tx-xsmall">
                <xsl:if test="CodiceValore">
                  <xsl:text> </xsl:text>
                  <xsl:value-of select="CodiceValore" />
                </xsl:if>
                <xsl:if test="CodiceTipo">
                  (<xsl:value-of select="CodiceTipo" />)
                </xsl:if>
              </div>
            </xsl:for-each>
          </td>
          <td class="descr">

            <xsl:if test="Descrizione">
              <xsl:value-of select="Descrizione" />
            </xsl:if>

            <xsl:if test="TipoCessionePrestazione">
              (<xsl:value-of select="TipoCessionePrestazione" />)
            </xsl:if>

            <xsl:if test="DataInizioPeriodo or DataFinePeriodo">
              <div class="tx-xsmall">
                <xsl:text>Periodo</xsl:text>
                <xsl:if test="DataInizioPeriodo">
                  <xsl:text> da </xsl:text>
                  <xsl:call-template name="FormatDateIta">
                    <xsl:with-param name="DateTime" select="DataInizioPeriodo" />
                  </xsl:call-template>
                </xsl:if>
                <xsl:if test="DataFinePeriodo">
                  <xsl:text> a </xsl:text>
                  <xsl:call-template name="FormatDateIta">
                    <xsl:with-param name="DateTime" select="DataFinePeriodo" />
                  </xsl:call-template>
                </xsl:if>
              </div>

            </xsl:if>


            <xsl:for-each select="AltriDatiGestionali"  >

              <xsl:if test=" translate( TipoDato,
                                     'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                     'abcdefghijklmnopqrstuvwxyz'
                                    ) != 'aswrelstd' 
									and 									
									translate( TipoDato,
                                     'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                     'abcdefghijklmnopqrstuvwxyz'
                                    ) != 'aswswhouse'   ">


                <div class="tx-xsmall">
                  <xsl:text>Tipo dato: </xsl:text>
                  <xsl:value-of select="TipoDato" />
                  <xsl:if test=" translate( TipoDato,
                                     'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                     'abcdefghijklmnopqrstuvwxyz'
                                    ) = 'aswlottsca' ">
                    <xsl:text> (dati relativi a lotti e scadenze) </xsl:text>
                  </xsl:if>

                </div>

                <xsl:if test="RiferimentoTesto">
                  <div class="tx-xsmall">
                    <xsl:choose>
                      <xsl:when test=" translate( TipoDato,
                                     'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                     'abcdefghijklmnopqrstuvwxyz'
                                    ) = 'aswlottsca' ">

                        <xsl:text>Lotto: </xsl:text>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:text>Rif. testo: </xsl:text>
                      </xsl:otherwise>
                    </xsl:choose>
                    <xsl:value-of select="RiferimentoTesto" />
                  </div>
                </xsl:if>



                <xsl:if test="RiferimentoData">
                  <div class="tx-xsmall">
                    <xsl:choose>
                      <xsl:when test=" translate( TipoDato,
                                     'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                     'abcdefghijklmnopqrstuvwxyz'
                                    ) = 'aswlottsca' ">

                        <xsl:text>Scadenza: </xsl:text>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:text>Rif. data: </xsl:text>
                      </xsl:otherwise>
                    </xsl:choose>

                    <xsl:call-template name="FormatDateIta">
                      <xsl:with-param name="DateTime" select="RiferimentoData" />
                    </xsl:call-template>

                  </div>
                </xsl:if>

                <xsl:if test="RiferimentoNumero">
                  <div class="tx-xsmall">
                    <xsl:choose>
                      <xsl:when test=" translate( TipoDato,
                                     'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                     'abcdefghijklmnopqrstuvwxyz'
                                    ) = 'aswlottsca' ">

                        <xsl:text>Quantità del suddetto lotto: </xsl:text>
                      </xsl:when>
                      <xsl:otherwise>
                        <xsl:text>Rif. numero: </xsl:text>
                      </xsl:otherwise>
                    </xsl:choose>
                    <xsl:value-of select="format-number(RiferimentoNumero,  '###.###.##0,00######', 'euro')" />
                  </div>
                </xsl:if>



              </xsl:if>

            </xsl:for-each>

						
						<xsl:if test="Ritenuta">
              <div class="tx-xsmall">
                Ritenuta: <xsl:value-of select="Ritenuta" />
              </div>
            </xsl:if>
						
					
            <xsl:if test="RiferimentoAmministrazione">
              <div class="tx-xsmall">
                RIF.AMM. <xsl:value-of select="RiferimentoAmministrazione" />
              </div>
            </xsl:if>

          </td>



          <td class="import2" >
            <xsl:if test="Quantita">
              <xsl:if test="number(Quantita)">
                <xsl:value-of select="format-number(Quantita,  '###.###.##0,00######', 'euro')" />
              </xsl:if>
            </xsl:if>
          </td>



          <td class="import" >
            <xsl:if test="PrezzoUnitario">
              <xsl:if test="number(PrezzoTotale)">

                <xsl:value-of select="format-number(PrezzoUnitario,  '###.###.##0,00######', 'euro')" />
              </xsl:if>
            </xsl:if>
          </td>

          <td class="textCenter" >
            <xsl:if test="UnitaMisura">
              <xsl:value-of select="UnitaMisura" />
            </xsl:if>

          </td>
          <td class="import" >

            <xsl:for-each select="ScontoMaggiorazione" >

              <div>

                <xsl:call-template name="FormatColSconto" >
                  <xsl:with-param name="tipo" select="Tipo" />
                  <xsl:with-param name="percentuale" select="Percentuale" />
                  <xsl:with-param name="importo" select="Importo" />
                </xsl:call-template>


              </div>
            </xsl:for-each>

          </td>

          <td class="import" >

						<!--MKT Visualizzo sempre la natura!!!-->
            <!--<xsl:if test="number(PrezzoTotale)">-->
              <xsl:call-template name="FormatIVA">
                <xsl:with-param name="Natura" select="Natura" />
                <xsl:with-param name="IVA" select="AliquotaIVA" />
              </xsl:call-template>
            <!--</xsl:if>-->

          </td>
          <td>
            <xsl:if test="PrezzoTotale">
							<!--MKT Visualizzo sempre prezzo totale!!!-->
              <!--<xsl:if test="number(PrezzoTotale)">-->
                <div class="import">
                  <xsl:value-of select="format-number(PrezzoTotale,  '###.###.##0,00######', 'euro')" />
                </div>
              <!--</xsl:if>-->


              <xsl:if test="OpzPrezzoTotale">
                <div class="tx-xsmall">
                  <xsl:value-of select="OpzPrezzoTotale" />
                </div>
              </xsl:if>
            </xsl:if>
          </td>

        </tr>

      </xsl:otherwise>
    </xsl:choose>

    <!--POST LINEA -->
    <xsl:for-each select="OpzPostLinea"  >
      <xsl:call-template name="AltraDescrizioneLinea">
        <xsl:with-param name="textDescrizione" select = "." />
      </xsl:call-template>
    </xsl:for-each>

  </xsl:template>


  <!-- Nel caso in cui ho un AltriDatiGestionali con aswrelstd o aswswhouse, mi permette
  di stampare tutti alti dati ma non aswrelstd e aswswhouse -->
  <xsl:template name="DettaglioLineeASW">
    <tr >
      <td>
      </td>

      <td >
        <xsl:text>------------------------</xsl:text>
        <xsl:for-each select="AltriDatiGestionali"  >

          <xsl:if test=" translate( TipoDato,
														 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
														 'abcdefghijklmnopqrstuvwxyz'
														) != 'aswrelstd' 
														and 									
														translate( TipoDato,
														 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
														 'abcdefghijklmnopqrstuvwxyz'
														) != 'aswswhouse'   ">

            <div class="tx-xsmall">
              <xsl:value-of select="RiferimentoTesto" />
              <xsl:text> </xsl:text>
              <xsl:value-of select="TipoDato" />
            </div>

          </xsl:if>

        </xsl:for-each>
      </td>

      <td >
      </td>


      <td  >
      </td>
      <td >
      </td>
      <td  >
      </td>

      <td  >
      </td>
      <td  >
      </td>
    </tr>

  </xsl:template>

  <xsl:template name="AltraDescrizioneLinea">
    <xsl:param name = "textDescrizione" />
    <!-- testo della descrizione -->
    <tr>
      <td >
      </td>

      <td >
        <div class="tx-xsmall">
          <xsl:value-of select="$textDescrizione" />
        </div>
      </td>

      <td>
      </td>


      <td>
      </td>
      <td>
      </td>
      <td>
      </td>

      <td>
      </td>
      <td>
      </td>
    </tr>

  </xsl:template>

  <xsl:template match="DatiRitenuta">


        <tr>
          <td >

            <xsl:if test="TipoRitenuta">

              <span>
                <xsl:value-of select="TipoRitenuta" />
              </span>
              <xsl:variable name="TR">
                <xsl:value-of select="TipoRitenuta" />
              </xsl:variable>
              <xsl:choose>
                <xsl:when test="$TR='RT01'">
                  Ritenuta persone fisiche
                </xsl:when>
                <xsl:when test="$TR='RT02'">
                  Ritenuta persone giuridiche
                </xsl:when>
								<xsl:when test="$TR='RT03'">
					Contributo INPS
                </xsl:when>
								<xsl:when test="$TR='RT04'">
					Contributo ENASARCO
                </xsl:when>
								<xsl:when test="$TR='RT05'">
					Contributo ENPAM
                </xsl:when>
								<xsl:when test="$TR='RT06'">
					Altro contributo previdenziale
                </xsl:when>
                <xsl:when test="$TR=''">
                </xsl:when>
                <xsl:otherwise>
                  <span>(!!! codice non previsto !!!)</span>
                </xsl:otherwise>
              </xsl:choose>

            </xsl:if>
          </td>

          <td class="import" >
            <xsl:if test="AliquotaRitenuta">
              <xsl:value-of select="format-number(AliquotaRitenuta,  '###.###.##0,00', 'euro')" />
            </xsl:if>

          </td>

          <td >
            <xsl:if test="CausalePagamento">

              <span>
                <xsl:value-of select="CausalePagamento" />
              </span>
              <xsl:variable name="CP">
                <xsl:value-of select="CausalePagamento" />
              </xsl:variable>
              <xsl:if test="$CP!=''">
                (decodifica come da modello CU)
              </xsl:if>

            </xsl:if>
          </td>

          <td class="import" >
            <xsl:if test="ImportoRitenuta">

              <xsl:value-of select="format-number(ImportoRitenuta,  '###.###.##0,00', 'euro')" />

            </xsl:if>
          </td>

        </tr>
     
  </xsl:template>

  <xsl:template match="DettaglioPagamento">

    <xsl:if test="Beneficiario">
      Beneficiario  <xsl:value-of select="Beneficiario" /> <br/>
    </xsl:if>

    <xsl:if test="IBAN">
      IBAN
      <xsl:value-of select="IBAN" /> <br/>
    </xsl:if>

    <xsl:if test="ABI or CAB or BIC ">

      <xsl:if test="ABI">
        ABI  <xsl:value-of select="ABI" />
      </xsl:if>

      <xsl:if test="CAB">
        CAB  <xsl:value-of select="CAB" />
      </xsl:if>

      <xsl:if test="BIC">
        BIC  <xsl:value-of select="BIC" />
      </xsl:if>
      <br/>
    </xsl:if>

    <xsl:if test="IstitutoFinanziario">
      <xsl:value-of select="IstitutoFinanziario" />
      <br/>
    </xsl:if>

    <xsl:if test="CodUfficioPostale">
      Codice ufficio postale
      <xsl:value-of select="CodUfficioPostale" />
      <br/>
    </xsl:if>

    <xsl:if test="TitoloQuietanzante or CognomeQuietanzante or NomeQuietanzante or CFQuietanzante">
      Quietanzante
      <xsl:value-of select="concat(TitoloQuietanzante , ' ',CognomeQuietanzante, ' ', NomeQuietanzante, ' ', CFQuietanzante )"/>
      <br/>
    </xsl:if>

    <xsl:if test="DataLimitePagamentoAnticipato or ScontoPagamentoAnticipato">

      <xsl:if test="DataLimitePagamentoAnticipato">
        Data limite pagamento anticipato <xsl:call-template name="FormatDateIta">
          <xsl:with-param name="DateTime" select="DataLimitePagamentoAnticipato" />
        </xsl:call-template>
      </xsl:if>

      <xsl:if test="ScontoPagamentoAnticipato">
        Sconto anticipato
        <xsl:value-of select="format-number(ScontoPagamentoAnticipato,  '###.###.##0,00', 'euro')" />
      </xsl:if>
      <br/>
    </xsl:if>

    <xsl:if test="DataDecorrenzaPenale or PenalitaPagamentiRitardati">

      <xsl:if test="DataDecorrenzaPenale">
        Data penale <xsl:call-template name="FormatDateIta">
          <xsl:with-param name="DateTime" select="DataDecorrenzaPenale" />
        </xsl:call-template>
      </xsl:if>

      <xsl:if test="PenalitaPagamentiRitardati">
        Importo penale
        <xsl:value-of select="format-number(PenalitaPagamentiRitardati,  '###.###.##0,00', 'euro')" />
      </xsl:if>

      <br/>
    </xsl:if>

    <xsl:if test="CodicePagamento">
      Codice pagamento  <xsl:value-of select="CodicePagamento" />
    </xsl:if>

  </xsl:template>

  <xsl:template name="FatturaElettronica">

    <xsl:param name="TipoFattura" />
    <xsl:param name="IsFPRS" />

    <xsl:if test="$TipoFattura">
			

			<!--Variabile che contiene il codice destinatario dall'HEADER perchè viene visualizzato nella sezione BODY -->
      <xsl:variable name="CodiceDestinatario" select="$TipoFattura/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario"/>
      <xsl:variable name="PecDestinatario" select="$TipoFattura/FatturaElettronicaHeader/DatiTrasmissione/PECDestinatario"/>

      <!--Variabile che contiene il codice destinatario dall'HEADER perchè viene visualizzato nella sezione BODY -->
			<!-- lo mettiamo sempre -->
			<!--
      <xsl:variable name="CodiceDestinatario" >

        <xsl:choose>
          <xsl:when test="$TipoFattura/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario='0000000'">
            <xsl:value-of select="$TipoFattura/FatturaElettronicaHeader/DatiTrasmissione/PECDestinatario" />
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="$TipoFattura/FatturaElettronicaHeader/DatiTrasmissione/CodiceDestinatario" />
          </xsl:otherwise>
        </xsl:choose>
      </xsl:variable>
			-->
      <div id="fattura-elettronica" class="page">
				
				<xsl:if test="$TipoFattura/DATA_RICEZIONE">
					<div style="margin-bottom: 10px">
						<div style="float: left;">
						Data Ricezione: <xsl:value-of select="$TipoFattura/DATA_RICEZIONE" />  
						</div>
						<xsl:if test="$TipoFattura/DATI_REGISTRAZIONE">
							<div style="float: right;">
							Protocollo: <xsl:value-of select="$TipoFattura/DATI_REGISTRAZIONE/PROTOCOLLO" /> del <xsl:value-of select="$TipoFattura/DATI_REGISTRAZIONE/DATA_REG" />
							</div>
						</xsl:if>
					</div>
					<br/><br/>
				</xsl:if>
				
				<!-- FatturaElettronicaHeader -->
        <xsl:if test="$TipoFattura/FatturaElettronicaHeader">

			  <xsl:if test="$TipoFattura/FatturaElettronicaHeader/NomeDocumento">
			      <table class="tbNoBorder">
				  <tr >
						<td>
                       <xsl:value-of select="$TipoFattura/FatturaElettronicaHeader/NomeDocumento"/>
				  </td>
				  </tr>
				  </table>
                </xsl:if>

          <table id="tbHeader" class="tbHeader">

            <tr >
              <td class="tdHead">

                <table class="tableHead">
                  <tr>

                    <td >

                      <!--INIZIO CEDENTE PRESTATORE-->
                      <div class="headBorder" >

                        <label class= "headerLabel">Cedente/prestatore (fornitore) </label>
                        <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore">

                          <xsl:choose>
                            <xsl:when test="DatiAnagrafici">
                              <!--DatiAnagrafici FPA\FPR-->

                              <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/DatiAnagrafici">

                                <div class="headContent mt5">
                                  <xsl:if test="IdFiscaleIVA">

                                    Identificativo fiscale ai fini IVA:
                                    <span>
                                      <xsl:value-of select="IdFiscaleIVA/IdPaese" />
                                      <xsl:value-of select="IdFiscaleIVA/IdCodice" />
                                    </span>

                                  </xsl:if>
                                </div>

                                <div class="headContent" >

                                  <xsl:if test="CodiceFiscale">

                                    Codice fiscale:
                                    <span>
                                      <xsl:value-of select="CodiceFiscale" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >

                                  <xsl:if test="Anagrafica/Denominazione">

                                    Denominazione:
                                    <span>
                                      <xsl:value-of select="Anagrafica/Denominazione" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >

                                  <xsl:if test="Anagrafica/Nome | Anagrafica/Cognome">

                                    Cognome nome:
																		
																		<xsl:if test="Anagrafica/Titolo">
                                      <span>
                                        <xsl:value-of select="Anagrafica/Titolo" />
                                        <xsl:text> </xsl:text>
                                      </span>
                                    </xsl:if>
																		
                                    <xsl:if test="Anagrafica/Cognome">
                                      <span>
                                        <xsl:value-of select="Anagrafica/Cognome" />
                                        <xsl:text> </xsl:text>
                                      </span>
                                    </xsl:if>
                                    <xsl:if test="Anagrafica/Nome">
                                      <span>
                                        <xsl:value-of select="Anagrafica/Nome" />
                                      </span>
                                    </xsl:if>

                                  </xsl:if>

                                </div>
																
																<div class="headContent" >
                                  <xsl:if test="Anagrafica/CodEORI">
                                    Cod EORI:
																		<span>
                                      <xsl:value-of select="Anagrafica/CodEORI" />
                                    </span>
																	</xsl:if>
                                </div>
																
																<div class="headContent" >
																	<xsl:if test="AlboProfessionale | NumeroIscrizioneAlbo | ProvinciaAlbo | DataIscrizioneAlbo">
																		Albo professionale:
																		
																		<xsl:if test="AlboProfessionale">
                                      <span>
                                        <xsl:value-of select="AlboProfessionale" />
                                        <xsl:text> </xsl:text>
                                      </span>
																		</xsl:if>
																	
																		<xsl:if test="ProvinciaAlbo">
                                      <span>
                                        <xsl:value-of select="ProvinciaAlbo" />
                                        <xsl:text>-</xsl:text>
                                      </span>
                                    </xsl:if>
																		
                                    <xsl:if test="NumeroIscrizioneAlbo">
                                      <span>
                                        <xsl:value-of select="NumeroIscrizioneAlbo" />
                                      </span>
                                    </xsl:if>
																		
																		<xsl:if test="DataIscrizioneAlbo">
																			<xsl:text> del </xsl:text>	
																			<xsl:call-template name="FormatDateIta">
																				<xsl:with-param name="DateTime" select="DataIscrizioneAlbo" />
																			</xsl:call-template>
																		</xsl:if>
																		
                                  </xsl:if>
																</div>
																
                                <div class="headContent" >

                                  <xsl:if test="RegimeFiscale">

                                    Regime fiscale:
                                    <span>
                                      <xsl:value-of select="RegimeFiscale" />
                                    </span>

                                    <xsl:variable name="RF">
                                      <xsl:value-of select="RegimeFiscale" />
                                    </xsl:variable>
                                    <xsl:choose>
                                      <xsl:when test="$RF='RF01'">
                                        ordinario
                                      </xsl:when>
                                      <xsl:when test="$RF='RF02'">
                                        contribuenti minimi
                                      </xsl:when>
                                      <xsl:when test="$RF='RF03'">
                                        nuove iniziative produttive - Non più valido in quanto abrogato dalla legge di stabilità 2015
                                      </xsl:when>
                                      <xsl:when test="$RF='RF04'">
                                        agricoltura e attività connesse e pesca
                                      </xsl:when>
                                      <xsl:when test="$RF='RF05'">
                                        vendita sali e tabacchi
                                      </xsl:when>
                                      <xsl:when test="$RF='RF06'">
                                        commercio fiammiferi
                                      </xsl:when>
                                      <xsl:when test="$RF='RF07'">
                                        editoria
                                      </xsl:when>
                                      <xsl:when test="$RF='RF08'">
                                        gestione servizi telefonia pubblica
                                      </xsl:when>
                                      <xsl:when test="$RF='RF09'">
                                        rivendita documenti di trasporto pubblico e di sosta
                                      </xsl:when>
                                      <xsl:when test="$RF='RF10'">
                                        intrattenimenti, giochi e altre attività di cui alla tariffa allegata al DPR 640/72
                                      </xsl:when>
                                      <xsl:when test="$RF='RF11'">
                                        agenzie viaggi e turismo
                                      </xsl:when>
                                      <xsl:when test="$RF='RF12'">
                                        agriturismo
                                      </xsl:when>
                                      <xsl:when test="$RF='RF13'">
                                        vendite a domicilio
                                      </xsl:when>
                                      <xsl:when test="$RF='RF14'">
                                        rivendita beni usati, oggetti d’arte, d’antiquariato o da collezione
                                      </xsl:when>
                                      <xsl:when test="$RF='RF15'">
                                        agenzie di vendite all’asta di oggetti d’arte, antiquariato o da collezione
                                      </xsl:when>
                                      <xsl:when test="$RF='RF16'">
                                        IVA per cassa P.A.
                                      </xsl:when>
                                      <xsl:when test="$RF='RF17'">
                                        IVA per cassa - art. 32-bis, D.L. 83/2012
                                      </xsl:when>
                                      <xsl:when test="$RF='RF19'">
                                        Regime forfettario
                                      </xsl:when>
                                      <xsl:when test="$RF='RF18'">
                                        altro
                                      </xsl:when>
                                      <xsl:when test="$RF=''">
                                      </xsl:when>
                                      <xsl:otherwise>
                                        <span>(!!! codice non previsto !!!)</span>
                                      </xsl:otherwise>
                                    </xsl:choose>

                                  </xsl:if>

                                </div>

                              </xsl:for-each>

                              <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Sede">

                                <div class="headContent" >

                                  <xsl:if test="Indirizzo">

                                    Indirizzo:
                                    <span>
                                      <xsl:value-of select="Indirizzo" />
                                      <xsl:text> </xsl:text>
                                      <xsl:value-of select="NumeroCivico" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >
                                  <span>
                                    <xsl:if test="Comune">

                                      Comune:
                                      <span>
                                        <xsl:value-of select="Comune" />

                                      </span>

                                    </xsl:if>
                                    <xsl:if test="Provincia">

                                      Provincia:
                                      <span>
                                        <xsl:value-of select="Provincia" />

                                      </span>

                                    </xsl:if>
                                  </span>


                                </div>
                                <div class="headContent" >

                                  <span>
                                    <xsl:if test="CAP">
                                      Cap:
                                      <span>
                                        <xsl:value-of select="CAP" />

                                      </span>
                                    </xsl:if>

                                    <xsl:if test="Nazione">

                                      Nazione:
                                      <span>
                                        <xsl:value-of select="Nazione" />

                                      </span>

                                    </xsl:if>
                                  </span>

                                </div>
                              </xsl:for-each>
															
															<xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/StabileOrganizzazione">

                                <div class="headContent mt5" >
																	<u>Stabile Organizzazione</u><br/>
                                  <xsl:if test="Indirizzo">
																		
                                    Indirizzo:
                                    <span>
                                      <xsl:value-of select="Indirizzo" />
                                      <xsl:text> </xsl:text>
                                      <xsl:value-of select="NumeroCivico" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >
                                  <span>
                                    <xsl:if test="Comune">

                                      Comune:
                                      <span>
                                        <xsl:value-of select="Comune" />

                                      </span>

                                    </xsl:if>
                                    <xsl:if test="Provincia">

                                      Provincia:
                                      <span>
                                        <xsl:value-of select="Provincia" />

                                      </span>

                                    </xsl:if>
                                  </span>


                                </div>
                                <div class="headContent" >

                                  <span>
                                    <xsl:if test="CAP">
                                      Cap:
                                      <span>
                                        <xsl:value-of select="CAP" />

                                      </span>
                                    </xsl:if>

                                    <xsl:if test="Nazione">

                                      Nazione:
                                      <span>
                                        <xsl:value-of select="Nazione" />

                                      </span>

                                    </xsl:if>
                                  </span>

                                </div>
                              </xsl:for-each>
															<xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/IscrizioneREA">
                                <div class="headContent" >
																	Iscrizione REA: 
																	<xsl:if test="Ufficio">
																		<span>
                                      <xsl:value-of select="Ufficio" />
                                      <xsl:text>-</xsl:text>
																		</span>
                                  </xsl:if>
																	
																	<xsl:if test="NumeroREA">
																		<span>
                                      <xsl:value-of select="NumeroREA" />
                                    </span>
                                  </xsl:if>
                                </div>
                                <div class="headContent" >
                                    <xsl:if test="CapitaleSociale">
                                      Capitale Sociale:
                                      <span>
																				<xsl:value-of select="format-number(CapitaleSociale,  '###.###.##0,00', 'euro')" />
																			</span>
                                    </xsl:if>
                                </div>
																<div class="headContent" >
																	<xsl:if test="SocioUnico">
																		Numero soci:
																		<span>
																			<xsl:value-of select="SocioUnico" />
																		</span>
																		<xsl:variable name="NS">
																			<xsl:value-of select="SocioUnico" />
																		</xsl:variable>
																		<xsl:choose>
																			<xsl:when test="$NS='SU'">
																				(socio unico)
																			</xsl:when>
																			<xsl:when test="$NS='SM'">
																				(pi&#249; soci)
																			</xsl:when>
																			<xsl:when test="$NS=''">
																			</xsl:when>
																			<xsl:otherwise>
																				<span>(!!! codice non previsto !!!)</span>
																			</xsl:otherwise>
																		</xsl:choose>
																	</xsl:if>
																</div>
																<div class="headContent" >
																	<xsl:if test="StatoLiquidazione">
																			Stato di liquidazione:
																			<span>
																				<xsl:value-of select="StatoLiquidazione" />
																			</span>

																			<xsl:variable name="SL">
																				<xsl:value-of select="StatoLiquidazione" />
																			</xsl:variable>
																			<xsl:choose>
																				<xsl:when test="$SL='LS'">
																					(in liquidazione)
																				</xsl:when>
																				<xsl:when test="$SL='LN'">
																					(non in liquidazione)
																				</xsl:when>
																				<xsl:when test="$SL=''">
																				</xsl:when>
																				<xsl:otherwise>
																					<span>(!!! codice non previsto !!!)</span>
																				</xsl:otherwise>
																			</xsl:choose>
																	</xsl:if>
																</div>
																
																
                              </xsl:for-each>
															
                              <div class="headContent" >

                                <xsl:if test="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Contatti/Telefono">

                                  Telefono:
                                  <span>
                                    <xsl:value-of select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Contatti/Telefono" />

                                  </span>

                                </xsl:if>


                              </div>

                              <div class="headContent" >

                                <xsl:if test="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Contatti/Email">

                                  Email:
                                  <span>
                                    <xsl:value-of select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Contatti/Email" />

                                  </span>

                                </xsl:if>



                              </div>

																														
															<div class="headContent" >
                                <xsl:if test="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Contatti/Fax">
                                  Fax:
                                  <span>
                                    <xsl:value-of select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Contatti/Fax" />
                                  </span>
                                </xsl:if>
                              </div>
															
                              <div class="headContent" >

                                <xsl:if test="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/RiferimentoAmministrazione">

                                  Riferimento Amministrazione:
                                  <span>
                                    <xsl:value-of select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/RiferimentoAmministrazione" />

                                  </span>

                                </xsl:if>



                              </div>

                            </xsl:when>
                            <xsl:otherwise>
                              <!--Anagrafica FPRS-->
                              <div class="headContent mt5">
                                <xsl:if test="IdFiscaleIVA">

                                  Identificativo fiscale ai fini IVA:
                                  <span>
                                    <xsl:value-of select="IdFiscaleIVA/IdPaese" />
                                    <xsl:value-of select="IdFiscaleIVA/IdCodice" />
                                  </span>

                                </xsl:if>
                              </div>

                              <div class="headContent" >

                                <xsl:if test="CodiceFiscale">

                                  Codice fiscale:
                                  <span>
                                    <xsl:value-of select="CodiceFiscale" />
                                  </span>

                                </xsl:if>

                              </div>

                              <xsl:if test="Denominazione">                              
                                <div class="headContent">

                                  Denominazione:
                                  <span>
                                    <xsl:value-of select="Denominazione" />
                                  </span>
                                </div>
                              </xsl:if>

                              <xsl:if test="Nome | Cognome">
                              <div class="headContent" >

                                  Cognome nome:

                                  <xsl:if test="Cognome">
                                    <span>
                                      <xsl:value-of select="Cognome" />
                                      <xsl:text> </xsl:text>
                                    </span>
                                  </xsl:if>
                                  <xsl:if test="Nome">
                                    <span>
                                      <xsl:value-of select="Nome" />
                                    </span>
                                  </xsl:if>
                              </div>
                              </xsl:if>

                              <div class="headContent" >

                                <xsl:if test="RegimeFiscale">

                                  Regime fiscale:
                                  <span>
                                    <xsl:value-of select="RegimeFiscale" />
                                  </span>

                                  <xsl:variable name="RF">
                                    <xsl:value-of select="RegimeFiscale" />
                                  </xsl:variable>
                                  <xsl:choose>
                                    <xsl:when test="$RF='RF01'">
                                      ordinario
                                    </xsl:when>
                                    <xsl:when test="$RF='RF02'">
                                      contribuenti minimi
                                    </xsl:when>
                                    <xsl:when test="$RF='RF03'">
                                      nuove iniziative produttive - Non più valido in quanto abrogato dalla legge di stabilità 2015
                                    </xsl:when>
                                    <xsl:when test="$RF='RF04'">
                                      agricoltura e attività connesse e pesca
                                    </xsl:when>
                                    <xsl:when test="$RF='RF05'">
                                      vendita sali e tabacchi
                                    </xsl:when>
                                    <xsl:when test="$RF='RF06'">
                                      commercio fiammiferi
                                    </xsl:when>
                                    <xsl:when test="$RF='RF07'">
                                      editoria
                                    </xsl:when>
                                    <xsl:when test="$RF='RF08'">
                                      gestione servizi telefonia pubblica
                                    </xsl:when>
                                    <xsl:when test="$RF='RF09'">
                                      rivendita documenti di trasporto pubblico e di sosta
                                    </xsl:when>
                                    <xsl:when test="$RF='RF10'">
                                      intrattenimenti, giochi e altre attività di cui alla tariffa allegata al DPR 640/72
                                    </xsl:when>
                                    <xsl:when test="$RF='RF11'">
                                      agenzie viaggi e turismo
                                    </xsl:when>
                                    <xsl:when test="$RF='RF12'">
                                      agriturismo
                                    </xsl:when>
                                    <xsl:when test="$RF='RF13'">
                                      vendite a domicilio
                                    </xsl:when>
                                    <xsl:when test="$RF='RF14'">
                                      rivendita beni usati, oggetti d’arte, d’antiquariato o da collezione
                                    </xsl:when>
                                    <xsl:when test="$RF='RF15'">
                                      agenzie di vendite all’asta di oggetti d’arte, antiquariato o da collezione
                                    </xsl:when>
                                    <xsl:when test="$RF='RF16'">
                                      IVA per cassa P.A.
                                    </xsl:when>
                                    <xsl:when test="$RF='RF17'">
                                      IVA per cassa - art. 32-bis, D.L. 83/2012
                                    </xsl:when>
                                    <xsl:when test="$RF='RF19'">
                                      Regime forfettario
                                    </xsl:when>
                                    <xsl:when test="$RF='RF18'">
                                      altro
                                    </xsl:when>
                                    <xsl:when test="$RF=''">
                                    </xsl:when>
                                    <xsl:otherwise>
                                      <span>!!! codice non previsto !!!</span>
                                    </xsl:otherwise>
                                  </xsl:choose>

                                </xsl:if>

                              </div>

                              <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CedentePrestatore/Sede">

                                <div class="headContent" >

                                  <xsl:if test="Indirizzo">

                                    Indirizzo:
                                    <span>
                                      <xsl:value-of select="Indirizzo" />
                                      <xsl:text> </xsl:text>
                                      <xsl:value-of select="NumeroCivico" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >
                                  <span>
                                    <xsl:if test="Comune">

                                      Comune:
                                      <span>
                                        <xsl:value-of select="Comune" />

                                      </span>

                                    </xsl:if>
                                    <xsl:if test="Provincia">

                                      Provincia:
                                      <span>
                                        <xsl:value-of select="Provincia" />

                                      </span>

                                    </xsl:if>
                                  </span>


                                </div>
                                <div class="headContent" >

                                  <span>
                                    <xsl:if test="CAP">
                                      Cap:
                                      <span>
                                        <xsl:value-of select="CAP" />

                                      </span>
                                    </xsl:if>

                                    <xsl:if test="Nazione">

                                      Nazione:
                                      <span>
                                        <xsl:value-of select="Nazione" />

                                      </span>

                                    </xsl:if>
                                  </span>

                                </div>
                              </xsl:for-each>
                            </xsl:otherwise>
                          </xsl:choose>

                        </xsl:for-each>
																								
												<xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/RappresentanteFiscale/DatiAnagrafici">
													<div class="headContent mt5">
														<u>Rappresentante fiscale:</u><br/>
													</div>
													<div class="headContent">
														<xsl:if test="IdFiscaleIVA">

															Identificativo fiscale ai fini IVA:
															<span>
																<xsl:value-of select="IdFiscaleIVA/IdPaese" />
																<xsl:value-of select="IdFiscaleIVA/IdCodice" />
															</span>

														</xsl:if>
													</div>

													<div class="headContent" >

														<xsl:if test="CodiceFiscale">

															Codice fiscale:
															<span>
																<xsl:value-of select="CodiceFiscale" />
															</span>

														</xsl:if>

													</div>

													<div class="headContent" >

														<xsl:if test="Anagrafica/Denominazione">

															Denominazione:
															<span>
																<xsl:value-of select="Anagrafica/Denominazione" />
															</span>

														</xsl:if>

													</div>

													<div class="headContent" >

														<xsl:if test="Anagrafica/Nome | Anagrafica/Cognome">

															Cognome nome:
															<!--MKT Inizio-->
															<xsl:if test="Anagrafica/Titolo">
																<span>
																	<xsl:value-of select="Anagrafica/Titolo" />
																	<xsl:text> </xsl:text>
																</span>
															</xsl:if>
															<!--MKT Fine-->
															<xsl:if test="Anagrafica/Cognome">
																<span>
																	<xsl:value-of select="Anagrafica/Cognome" />
																	<xsl:text> </xsl:text>
																</span>
															</xsl:if>
															<xsl:if test="Anagrafica/Nome">
																<span>
																	<xsl:value-of select="Anagrafica/Nome" />
																</span>
															</xsl:if>

														</xsl:if>

													</div>

													<div class="headContent" >
														<xsl:if test="Anagrafica/CodEORI">
															Cod EORI:
															<span>
																<xsl:value-of select="Anagrafica/CodEORI" />
															</span>
														</xsl:if>
													</div>
												</xsl:for-each>
												
                      </div>

                      <!--FINE CEDENTE PRESTATORE-->

                    </td>
                  </tr>

                </table>



              </td>
              <td class="tdHead">

                <!--INIZIO CESSIONARIO COMMITTENTE-->
                <table class="tableHead">
                  <tr>
                    <td >

                      <div class="headBorder" >
                        <label class= "headerLabel"  >Cessionario/committente (cliente) </label>
                        <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CessionarioCommittente">
                          <xsl:choose>
                            <xsl:when test="DatiAnagrafici">
                              <!--DatiAnagrafici FPA\FPR-->
                              <xsl:for-each select="DatiAnagrafici">

                                <div class="headContent mt5" >
                                  <xsl:if test="IdFiscaleIVA">

                                    Identificativo fiscale ai fini IVA:
                                    <span>
                                      <xsl:value-of select="IdFiscaleIVA/IdPaese" />
                                      <xsl:value-of select="IdFiscaleIVA/IdCodice" />
                                    </span>

                                  </xsl:if>
                                </div>

                                <div class="headContent" >

                                  <xsl:if test="CodiceFiscale">

                                    Codice fiscale:
                                    <span>
                                      <xsl:value-of select="CodiceFiscale" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >

                                  <xsl:if test="Anagrafica/Denominazione">

                                    Denominazione:
                                    <span>
                                      <xsl:value-of select="Anagrafica/Denominazione" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >

                                  <xsl:if test="Anagrafica/Nome | Anagrafica/Cognome | Anagrafica/Titolo">

                                    Cognome nome:
																		
																		<xsl:if test="Anagrafica/Titolo">
                                      <span>
                                        <xsl:value-of select="Anagrafica/Titolo" />
                                        <xsl:text> </xsl:text>
                                      </span>
                                    </xsl:if>
																		
                                    <xsl:if test="Anagrafica/Cognome">
                                      <span>
                                        <xsl:value-of select="Anagrafica/Cognome" />
                                        <xsl:text> </xsl:text>
                                      </span>
                                    </xsl:if>
                                    <xsl:if test="Anagrafica/Nome">
                                      <span>
                                        <xsl:value-of select="Anagrafica/Nome" />
                                      </span>
                                    </xsl:if>

                                  </xsl:if>

                                </div>
																
																<div class="headContent" >
																	<xsl:if test="Anagrafica/CodEORI">
																		Cod EORI:
																		<span>
																			<xsl:value-of select="Anagrafica/CodEORI" />
																		</span>
																	</xsl:if>
																</div>	
																


                              </xsl:for-each>

                              <xsl:for-each select="Sede">

                                <div class="headContent" >

                                  <xsl:if test="Indirizzo">

                                    Indirizzo:
                                    <span>
                                      <xsl:value-of select="Indirizzo" />
                                      <xsl:text> </xsl:text>
                                      <xsl:value-of select="NumeroCivico" />
                                    </span>

                                  </xsl:if>

                                </div>



                                <div class="headContent" >
                                  <span>
                                    <xsl:if test="Comune">

                                      Comune:
                                      <span>
                                        <xsl:value-of select="Comune" />

                                      </span>

                                    </xsl:if>
                                    <xsl:if test="Provincia">

                                      Provincia:
                                      <span>
                                        <xsl:value-of select="Provincia" />

                                      </span>

                                    </xsl:if>
                                  </span>


                                </div>
                                <div class="headContent" >

                                  <span>
                                    <xsl:if test="CAP">
                                      Cap:
                                      <span>
                                        <xsl:value-of select="CAP" />

                                      </span>
                                    </xsl:if>

                                    <xsl:if test="Nazione">

                                      Nazione:
                                      <span>
                                        <xsl:value-of select="Nazione" />

                                      </span>

                                    </xsl:if>
                                  </span>

                                </div>
																
																<xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CessionarioCommittente/StabileOrganizzazione">

                                <div class="headContent mt5" >
																	<u>Stabile Organizzazione</u><br/>
                                  <xsl:if test="Indirizzo">
																		
                                    Indirizzo:
                                    <span>
                                      <xsl:value-of select="Indirizzo" />
                                      <xsl:text> </xsl:text>
                                      <xsl:value-of select="NumeroCivico" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >
                                  <span>
                                    <xsl:if test="Comune">

                                      Comune:
                                      <span>
                                        <xsl:value-of select="Comune" />

                                      </span>

                                    </xsl:if>
                                    <xsl:if test="Provincia">

                                      Provincia:
                                      <span>
                                        <xsl:value-of select="Provincia" />

                                      </span>

                                    </xsl:if>
                                  </span>


                                </div>
                                <div class="headContent" >

                                  <span>
                                    <xsl:if test="CAP">
                                      Cap:
                                      <span>
                                        <xsl:value-of select="CAP" />

                                      </span>
                                    </xsl:if>

                                    <xsl:if test="Nazione">

                                      Nazione:
                                      <span>
                                        <xsl:value-of select="Nazione" />

                                      </span>

                                    </xsl:if>
                                  </span>

                                </div>
                              </xsl:for-each>
															
															<xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CessionarioCommittente/RappresentanteFiscale">
															<div class="headContent mt5">
																	<u>Rappresentante fiscale:</u><br/>
																</div>
																<div class="headContent">
																	<xsl:if test="IdFiscaleIVA">

																		Identificativo fiscale ai fini IVA:
																		<span>
																			<xsl:value-of select="IdFiscaleIVA/IdPaese" />
																			<xsl:value-of select="IdFiscaleIVA/IdCodice" />
																		</span>

																	</xsl:if>
																</div>

																<div class="headContent" >

																	<xsl:if test="Denominazione">

																		Denominazione:
																		<span>
																			<xsl:value-of select="Denominazione" />
																		</span>

																	</xsl:if>

																</div>

																<div class="headContent" >

																	<xsl:if test="Nome | Cognome">

																		Cognome nome:
																		
																		<xsl:if test="Cognome">
																			<span>
																				<xsl:value-of select="Cognome" />
																				<xsl:text> </xsl:text>
																			</span>
																		</xsl:if>
																		<xsl:if test="Nome">
																			<span>
																				<xsl:value-of select="Nome" />
																			</span>
																		</xsl:if>

																	</xsl:if>

																</div>

																<div class="headContent" >
																	<xsl:if test="Anagrafica/CodEORI">
																		Cod EORI:
																		<span>
																			<xsl:value-of select="Anagrafica/CodEORI" />
																		</span>
																	</xsl:if>
																</div>
															</xsl:for-each>
															
                                <div class="headContent mt5" >

                                  <xsl:if test="$PecDestinatario">

                                    Pec: <span>
                                      <xsl:value-of select="$PecDestinatario" />
                                    </span>

                                  </xsl:if>

                                </div>


                              </xsl:for-each>
                            </xsl:when>
                            <xsl:otherwise>
                              <!--Anagrafica FPRS-->
                              <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CessionarioCommittente/IdentificativiFiscali">
                                <div class="headContent mt5" >
                                  <xsl:if test="IdFiscaleIVA">

                                    Identificativo fiscale ai fini IVA:
                                    <span>
                                      <xsl:value-of select="IdFiscaleIVA/IdPaese" />
                                      <xsl:value-of select="IdFiscaleIVA/IdCodice" />
                                    </span>

                                  </xsl:if>
                                </div>

                                <div class="headContent" >

                                  <xsl:if test="CodiceFiscale">

                                    Codice fiscale:
                                    <span>
                                      <xsl:value-of select="CodiceFiscale" />
                                    </span>

                                  </xsl:if>

                                </div>
                              </xsl:for-each>
                              <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CessionarioCommittente/AltriDatiIdentificativi">
                                <div class="headContent" >

                                  <xsl:if test="Denominazione">

                                    Denominazione:
                                    <span>
                                      <xsl:value-of select="Denominazione" />
                                    </span>

                                  </xsl:if>

                                </div>

                                <div class="headContent" >

                                  <xsl:if test="Nome | Cognome">

                                    Cognome nome:

                                    <xsl:if test="Cognome">
                                      <span>
                                        <xsl:value-of select="Cognome" />
                                        <xsl:text> </xsl:text>
                                      </span>
                                    </xsl:if>
                                    <xsl:if test="Nome">
                                      <span>
                                        <xsl:value-of select="Nome" />
                                      </span>
                                    </xsl:if>

                                  </xsl:if>

                                </div>

                                <xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/CessionarioCommittente/AltriDatiIdentificativi/Sede">

                                  <div class="headContent" >

                                    <xsl:if test="Indirizzo">

                                      Indirizzo:
                                      <span>
                                        <xsl:value-of select="Indirizzo" />
                                        <xsl:text> </xsl:text>
                                        <xsl:value-of select="NumeroCivico" />
                                      </span>

                                    </xsl:if>

                                  </div>



                                  <div class="headContent" >
                                    <span>
                                      <xsl:if test="Comune">

                                        Comune:
                                        <span>
                                          <xsl:value-of select="Comune" />

                                        </span>

                                      </xsl:if>
                                      <xsl:if test="Provincia">

                                        Provincia:
                                        <span>
                                          <xsl:value-of select="Provincia" />

                                        </span>

                                      </xsl:if>
                                    </span>


                                  </div>
                                  <div class="headContent" >

                                    <span>
                                      <xsl:if test="CAP">
                                        Cap:
                                        <span>
                                          <xsl:value-of select="CAP" />

                                        </span>
                                      </xsl:if>

                                      <xsl:if test="Nazione">

                                        Nazione:
                                        <span>
                                          <xsl:value-of select="Nazione" />

                                        </span>

                                      </xsl:if>
                                    </span>

                                  </div>
                                  <div class="headContent" >

                                    <xsl:if test="$PecDestinatario">

                                      Pec: <span>
                                        <xsl:value-of select="$PecDestinatario" />
                                      </span>

                                    </xsl:if>

                                  </div>


                                </xsl:for-each>

                              </xsl:for-each>
                            </xsl:otherwise>
                          </xsl:choose>
                        </xsl:for-each>
                      </div>

                    </td>
                  </tr>

                </table>
                <!--FINE CESSIONARIO COMMITTENTE-->
              </td>
            </tr>


          </table>


        </xsl:if>
        <div style="height:10px" > </div>

        <!-- FINE FatturaElettronicaHeader -->
				
				<!--INIZIO BODY-->

        <xsl:for-each select="$TipoFattura/FatturaElettronicaBody" >


          <xsl:variable name="BodyIndex" select="position()"/>
          
          <!-- Conforme Standard AssoSoftware se altridatigestionali presenta ASWRELSTD   -->
          <xsl:variable name="posASWRELSTD" >
            <xsl:for-each select="DatiBeniServizi/DettaglioLinee">
              <xsl:variable name="DettaglioLinee" select="."/>
              
              <xsl:variable name="posDettaglioLinee" select="position()"/>
              <xsl:for-each select="AltriDatiGestionali">

                <xsl:if test=" translate( TipoDato,
                                     'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                                     'abcdefghijklmnopqrstuvwxyz'
                                    ) = 'aswrelstd'">

                  <xsl:value-of select="number($posDettaglioLinee)"/>

                </xsl:if>
              </xsl:for-each>

            </xsl:for-each>
          </xsl:variable>
          <!-- FINE conforme AssoSoftware -->


          <table class="tbFoglio">

            <!-- TIPOLOGIA DOCUMENTO TESTATA-->
            <thead>
              <tr>

                <th>Tipologia documento</th>
				<xsl:if test="$IsFPRS='0'">
                <th class="perc">Art. 73</th>
				</xsl:if>
				
				<xsl:if test="$IsFPRS='1'">
                  <th class="perc">Imposta bollo</th>
                </xsl:if>
				
                <th >Numero documento</th>
                <th class="data">Data documento</th>
                <th >Codice destinatario</th>

              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <xsl:if test="DatiGenerali/DatiGeneraliDocumento/TipoDocumento">

                    <xsl:value-of select="DatiGenerali/DatiGeneraliDocumento/TipoDocumento" />


                    <xsl:variable name="TD">
                      <xsl:value-of select="DatiGenerali/DatiGeneraliDocumento/TipoDocumento" />
                    </xsl:variable>
                    <xsl:choose>
                      <xsl:when test="$TD='TD01'">
                        fattura
                      </xsl:when>
                      <xsl:when test="$TD='TD02'">
                        acconto/anticipo su fattura
                      </xsl:when>
                      <xsl:when test="$TD='TD03'">
                        acconto/anticipo su parcella
                      </xsl:when>
                      <xsl:when test="$TD='TD04'">
                        nota di credito
                      </xsl:when>
                      <xsl:when test="$TD='TD05'">
                        nota di debito
                      </xsl:when>
                      <xsl:when test="$TD='TD06'">
                        parcella
                      </xsl:when>
                     <xsl:when test="$TD='TD16'">
							integrazione fattura reverse charge interno
						</xsl:when>
						<xsl:when test="$TD='TD17'">
							integrazione/autofattura per acquisto servizi da estero
						</xsl:when>
						<xsl:when test="$TD='TD18'">
							integrazione per acquisto beni intracomunitari
						</xsl:when>
						<xsl:when test="$TD='TD19'">
							integrazione/autofattura per acquisto beni ex art.17 c.2 DPR 633/72
						</xsl:when>
                      <xsl:when test="$TD='TD20'">
							autofattura per regolarizzazione e integrazione delle fatture - art.6 c.8 d.lgs.471/97 o art.46 c.5 D.L.331/93
                      </xsl:when>
						<xsl:when test="$TD='TD21'">
							autofattura per splafonamento
						</xsl:when>
						<xsl:when test="$TD='TD22'">
							estrazione beni da Deposito IVA
						</xsl:when>
						<xsl:when test="$TD='TD23'">
							estrazione beni da Deposito IVA con versamento IVA
						</xsl:when>
						<xsl:when test="$TD='TD24'">
							fattura differita - art.21 c.4 lett. a)
						</xsl:when>
						<xsl:when test="$TD='TD25'">
							fattura differita - art.21 c.4 terzo periodo lett. b)
						</xsl:when>
						<xsl:when test="$TD='TD26'">
							cessione di beni ammortizzabili e per passaggi interni - art.36 DPR 633/72
						</xsl:when>
						<xsl:when test="$TD='TD27'">
							fattura per autoconsumo o per cessioni gratuite senza rivalsa
						</xsl:when>
						
                      <!--FPRS-->
                      <xsl:when test="$TD='TD07'">
                        fattura semplificata
                      </xsl:when>
                      <xsl:when test="$TD='TD08'">
                        nota di credito semplificata
                      </xsl:when>
                      <xsl:when test="$TD='TD09'">
                        nota di debito semplificata
                      </xsl:when>
                      <xsl:when test="$TD=''">
                      </xsl:when>
                      <xsl:otherwise>
                        <span>(!!! codice non previsto !!!)</span>
                      </xsl:otherwise>
                    </xsl:choose>

                  </xsl:if>
                </td>

				<xsl:if test="$IsFPRS='0'">
                <td class="ritenuta"  >
                  <xsl:if test="DatiGenerali/DatiGeneraliDocumento/Art73">
                    <xsl:value-of select="DatiGenerali/DatiGeneraliDocumento/Art73" />
                  </xsl:if>
                </td>
				</xsl:if>

				 <xsl:if test="$IsFPRS='1'">
                  <td class="textCenter">
                    <xsl:if test="DatiGenerali/DatiGeneraliDocumento/BolloVirtuale">
                      <xsl:value-of select="DatiGenerali/DatiGeneraliDocumento/BolloVirtuale" />
                    </xsl:if>
                  </td>
                </xsl:if>

                <td class="textCenter" >

                  <xsl:if test="DatiGenerali/DatiGeneraliDocumento/Numero">
                    <xsl:value-of select="DatiGenerali/DatiGeneraliDocumento/Numero" />
                  </xsl:if>
                </td>
                <td class="data" >

                  <xsl:if test="DatiGenerali/DatiGeneraliDocumento/Data">
                    <xsl:call-template name="FormatDateIta">
                      <xsl:with-param name="DateTime" select="DatiGenerali/DatiGeneraliDocumento/Data" />
                    </xsl:call-template>
                  </xsl:if>

                </td>

                <td class="textCenter" >
                  <xsl:if test="$CodiceDestinatario">
										<xsl:value-of select="$CodiceDestinatario" />
                  </xsl:if>
                </td>

              </tr>

              <!--FINE TIPOLOGIA Documento TESTATA-->
            </tbody>
          </table>

          <xsl:if test="DatiGenerali/DatiGeneraliDocumento/Causale">
            <div class="separa"> </div>
            <table class="tbFoglio">

              <!-- TIPOLOGIA DOCUMENTO TESTATA - parte causale-->
              <thead>
                <tr>
                  <th>Causale</th>
                </tr>
              </thead>
              <tbody>
                <tr>

                  <td >
                    <xsl:if test="DatiGenerali/DatiGeneraliDocumento/Causale">

                      <xsl:for-each select="DatiGenerali/DatiGeneraliDocumento/Causale"  >
												<xsl:value-of select="." />
												<xsl:text> </xsl:text> 
                      </xsl:for-each>

                    </xsl:if>
                  </td>

                </tr>

                <!--FINE TIPOLOGIA Documento TESTATA - parte causale -->
              </tbody>
            </table>
          </xsl:if>

          <div class="separa"> </div>

          <xsl:choose>
            <xsl:when test="$IsFPRS='1'">

              <!--  Dettaglio Linee   -->
              <table class="tbFoglio"  >

                <thead>
                  <tr>
                    <th>Descrizione</th>
                    <th class="perc">Imposta</th>
                    <th class="perc2">%IVA</th>
                    <th class="ximport">Prezzo totale</th>

                  </tr>
                </thead>
                <tbody>

                  <xsl:for-each select="DatiBeniServizi" >

                    <tr>
                      <td>

                        <xsl:if test="Descrizione">
                          <xsl:value-of select="Descrizione" />
                        </xsl:if>

                        <xsl:if test="RiferimentoNormativo">
                          <div class="tx-xsmall">
                            RIF.NORM. <xsl:value-of select="RiferimentoNormativo" />
                          </div>
                        </xsl:if>

                      </td>
                      <td class="import" >

                        <xsl:if test="DatiIVA/Imposta">
                          <xsl:value-of select="format-number(DatiIVA/Imposta,  '###.###.##0,00', 'euro')" />
                        </xsl:if>
                      </td>
                      <td class="import" >

                        <xsl:call-template name="FormatIVA">
                          <xsl:with-param name="Natura" select="Natura" />
                          <xsl:with-param name="IVA" select="DatiIVA/Aliquota" />
                        </xsl:call-template>

                      </td>
                      <td class="import" >
                        <xsl:if test="Importo">
                          <xsl:value-of select="format-number(Importo,  '###.###.##0,00', 'euro')" />
                        </xsl:if>
                      </td>
                    </tr>

                  </xsl:for-each>


                </tbody>

              </table>

            </xsl:when>
            <xsl:otherwise>

              <!--  Dettaglio Linee   -->
              <table class="tbFoglio"  >

                <thead>
                  <tr>
										
                    <th class="cod">Cod.</th>
                    <th class="descr">Descrizione</th>
                    <th class="import2" >Quantità</th>
                    <th class="import2">Prezzo</th>
                    <th class="perc2">UM</th>
                    <th class="perc">Sc.</th>
                    <th class="perc2">%IVA</th>
                    <th class="ximport">Prezzo totale</th>
											
                  </tr>
                </thead>
                <tbody>


                  <xsl:if test="count(DatiGenerali/DatiOrdineAcquisto[not(./RiferimentoNumeroLinea) or normalize-space(./RiferimentoNumeroLinea)='']) + 
				  count(DatiGenerali/DatiContratto[not(./RiferimentoNumeroLinea) or normalize-space(./RiferimentoNumeroLinea)='']) +
				  count(DatiGenerali/DatiDDT[not(./RiferimentoNumeroLinea) or normalize-space(./RiferimentoNumeroLinea)='']) +
				  count(DatiGenerali/DatiFattureCollegate[not(./RiferimentoNumeroLinea) or  normalize-space(./RiferimentoNumeroLinea)='']) +
				  count(DatiGenerali/DatiConvenzione[not(./RiferimentoNumeroLinea) or  normalize-space(./RiferimentoNumeroLinea)=''])+
				  count(DatiGenerali/DatiRicezione[not(./RiferimentoNumeroLinea) or  normalize-space(./RiferimentoNumeroLinea)=''])  > 0 " >


                    <!-- Verifica che DatiOrdineAcquisto non siano senza riferimento numero linea in questo modo bisogna creare la linea di info 	  -->
                    <xsl:for-each select="DatiGenerali/DatiOrdineAcquisto[not(./RiferimentoNumeroLinea) or  normalize-space(./RiferimentoNumeroLinea)=''] " >

                      <xsl:call-template name="DatiCorrelati" >
                        <xsl:with-param name="Prefix"   select='"Vs.Ord. "'/>
                        <xsl:with-param name="IdDocumento" select="IdDocumento"/>
                        <xsl:with-param name="Data" select="Data"/>
                        <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
                        <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
												
												<xsl:with-param name="NumItem" select="NumItem"/>
												<xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
												
                      </xsl:call-template >
                    </xsl:for-each>

                    <!-- Verifica che DatiContratto non siano senza riferimento numero linea in questo modo bisogna creare la linea di info 	  -->

                    <xsl:for-each select="DatiGenerali/DatiContratto[not(./RiferimentoNumeroLinea) or  normalize-space(./RiferimentoNumeroLinea)=''] " >
                      <xsl:call-template name="DatiCorrelati" >
                        <xsl:with-param name="Prefix"   select='"Contratto "'/>
                        <xsl:with-param name="IdDocumento" select="IdDocumento"/>
                        <xsl:with-param name="Data" select="Data"/>
                        <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
                        <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
												
												<xsl:with-param name="NumItem" select="NumItem"/>
												<xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
												
                      </xsl:call-template >

                    </xsl:for-each>

                    <!-- Verifica che DatiConvenzione non siano senza riferimento numero linea in questo modo bisogna creare la linea di info 	  -->

                    <xsl:for-each select="DatiGenerali/DatiConvenzione[not(./RiferimentoNumeroLinea) or  normalize-space(./RiferimentoNumeroLinea)=''] " >
                      <xsl:call-template name="DatiCorrelati" >
                        <xsl:with-param name="Prefix"   select='"Convenzione "'/>
                        <xsl:with-param name="IdDocumento" select="IdDocumento"/>
                        <xsl:with-param name="Data" select="Data"/>
                        <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
                        <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
												
												<xsl:with-param name="NumItem" select="NumItem"/>
												<xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>	
												
                      </xsl:call-template >

                    </xsl:for-each>

                    <!-- Verifica che DatiRicezione non siano senza riferimento numero linea in questo modo bisogna creare la linea di info 	  -->
                    <xsl:for-each select="DatiGenerali/DatiRicezione[not(./RiferimentoNumeroLinea) or  normalize-space(./RiferimentoNumeroLinea)=''] " >
                      <xsl:call-template name="DatiCorrelati" >
                        <xsl:with-param name="Prefix"   select='"Ricezione "'/>
                        <xsl:with-param name="IdDocumento" select="IdDocumento"/>
                        <xsl:with-param name="Data" select="Data"/>
                        <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
                        <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
												
												<xsl:with-param name="NumItem" select="NumItem"/>
												<xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
												
                      </xsl:call-template >

                    </xsl:for-each>

                    <!-- Verifica che DatiFattureCollegate non siano senza riferimento numero linea in questo modo bisogna creare la linea di info 	  -->

                    <xsl:for-each select="DatiGenerali/DatiFattureCollegate[not(./RiferimentoNumeroLinea) or normalize-space(./RiferimentoNumeroLinea)=''] " >

                      <xsl:call-template name="DatiCorrelati" >
                        <xsl:with-param name="Prefix"   select='"Fatt.Coll. "'/>
                        <xsl:with-param name="IdDocumento" select="IdDocumento"/>
                        <xsl:with-param name="Data" select="Data"/>
                        <xsl:with-param name="CodiceCUP" select="CodiceCUP"/>
                        <xsl:with-param name="CodiceCIG" select="CodiceCIG"/>
												
												<xsl:with-param name="NumItem" select="NumItem"/>
												<xsl:with-param name="CodiceCommessaConvenzione" select="CodiceCommessaConvenzione"/>
												
                      </xsl:call-template >

                    </xsl:for-each>

                    <xsl:for-each select="DatiGenerali/DatiDDT[not(./RiferimentoNumeroLinea) or normalize-space(./RiferimentoNumeroLinea)=''] ">
                      <xsl:apply-templates select="."/>	<!-- apply DatiDDT template -->
                    </xsl:for-each>

                    <xsl:call-template name="AltraDescrizioneLinea">
                      <xsl:with-param name="textDescrizione" select = '"------------------------"' />
                    </xsl:call-template>
                  </xsl:if>



                  <xsl:for-each select="DatiBeniServizi/DettaglioLinee" >
                    <xsl:apply-templates select=".">
                      <xsl:with-param name="r" select="position()"/>
                      <xsl:with-param name="posASWRELSTD" select="$posASWRELSTD"/>
                      <xsl:with-param name="TipoFattura" select="$TipoFattura"/>
                      <xsl:with-param name="IndiceBody" select="$BodyIndex"/>
                    </xsl:apply-templates>
                  </xsl:for-each>


                </tbody>

              </table>

              <!--   Dati Cassa Prevvidenziale    -->
              <xsl:if test="DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale">
                <div class="separa"> </div>

                <table class="tbFoglio">

                  <thead>
                    <tr>
                      <th class="title">Dati Cassa Previdenziale</th>
                      <th>Imponibile</th>
                      <th class="perc">%Contr.</th>
                      <th class="perc">Ritenuta</th>
                      <th class="perc">%IVA</th>
                      <th >Importo</th>
                    </tr>
                  </thead>
                  <tbody>
                    <xsl:for-each select="DatiGenerali/DatiGeneraliDocumento/DatiCassaPrevidenziale"  >

                      <tr>
                        <td>
                          <xsl:if test="TipoCassa">

                            <span>
                              <xsl:value-of select="TipoCassa" />
                            </span>
                            <xsl:variable name="TC">
                              <xsl:value-of select="TipoCassa" />
                            </xsl:variable>
                            <xsl:choose>
                              <xsl:when test="$TC='TC01'">
                                (Cassa Nazionale Previdenza e Assistenza Avvocati
                                e Procuratori legali)
                              </xsl:when>
                              <xsl:when test="$TC='TC02'">
                                (Cassa Previdenza Dottori Commercialisti)
                              </xsl:when>
                              <xsl:when test="$TC='TC03'">
                                (Cassa Previdenza e Assistenza Geometri)
                              </xsl:when>
                              <xsl:when test="$TC='TC04'">
                                (Cassa Nazionale Previdenza e Assistenza
                                Ingegneri e Architetti liberi profess.)
                              </xsl:when>
                              <xsl:when test="$TC='TC05'">
                                (Cassa Nazionale del Notariato)
                              </xsl:when>
                              <xsl:when test="$TC='TC06'">
                                (Cassa Nazionale Previdenza e Assistenza
                                Ragionieri e Periti commerciali)
                              </xsl:when>
                              <xsl:when test="$TC='TC07'">
                                (Ente Nazionale Assistenza Agenti e Rappresentanti
                                di Commercio-ENASARCO)
                              </xsl:when>
                              <xsl:when test="$TC='TC08'">
                                (Ente Nazionale Previdenza e Assistenza Consulenti
                                del Lavoro-ENPACL)
                              </xsl:when>
                              <xsl:when test="$TC='TC09'">
                                (Ente Nazionale Previdenza e Assistenza
                                Medici-ENPAM)
                              </xsl:when>
                              <xsl:when test="$TC='TC10'">
                                (Ente Nazionale Previdenza e Assistenza
                                Farmacisti-ENPAF)
                              </xsl:when>
                              <xsl:when test="$TC='TC11'">
                                (Ente Nazionale Previdenza e Assistenza
                                Veterinari-ENPAV)
                              </xsl:when>
                              <xsl:when test="$TC='TC12'">
                                (Ente Nazionale Previdenza e Assistenza Impiegati
                                dell'Agricoltura-ENPAIA)
                              </xsl:when>
                              <xsl:when test="$TC='TC13'">
                                (Fondo Previdenza Impiegati Imprese di Spedizione
                                e Agenzie Marittime)
                              </xsl:when>
                              <xsl:when test="$TC='TC14'">
                                (Istituto Nazionale Previdenza Giornalisti
                                Italiani-INPGI)
                              </xsl:when>
                              <xsl:when test="$TC='TC15'">
                                (Opera Nazionale Assistenza Orfani Sanitari
                                Italiani-ONAOSI)
                              </xsl:when>
                              <xsl:when test="$TC='TC16'">
                                (Cassa Autonoma Assistenza Integrativa
                                Giornalisti Italiani-CASAGIT)
                              </xsl:when>
                              <xsl:when test="$TC='TC17'">
                                (Ente Previdenza Periti Industriali e Periti
                                Industriali Laureati-EPPI)
                              </xsl:when>
                              <xsl:when test="$TC='TC18'">
                                (Ente Previdenza e Assistenza
                                Pluricategoriale-EPAP)
                              </xsl:when>
                              <xsl:when test="$TC='TC19'">
                                (Ente Nazionale Previdenza e Assistenza
                                Biologi-ENPAB)
                              </xsl:when>
                              <xsl:when test="$TC='TC20'">
                                (Ente Nazionale Previdenza e Assistenza
                                Professione Infermieristica-ENPAPI)
                              </xsl:when>
                              <xsl:when test="$TC='TC21'">
                                (Ente Nazionale Previdenza e Assistenza
                                Psicologi-ENPAP)
                              </xsl:when>
                              <xsl:when test="$TC='TC22'">
                                (INPS)
                              </xsl:when>
                              <xsl:when test="$TC=''">
                              </xsl:when>
                              <xsl:otherwise>
                                <span>(!!! codice non previsto !!!)</span>
                              </xsl:otherwise>
                            </xsl:choose>

                          </xsl:if>
													
													<xsl:if test="RiferimentoAmministrazione">
														<br/>Rif:  <xsl:value-of select="RiferimentoAmministrazione" />
													</xsl:if>
													
                        </td>
                        <td class="import">
                          <xsl:if test="ImponibileCassa">
                            <xsl:value-of select="format-number(ImponibileCassa,  '###.###.##0,00', 'euro')" />
                          </xsl:if>
                        </td>

                        <td class="import">
                          <xsl:if test="AlCassa">

                            <xsl:value-of select="format-number(AlCassa,  '###.###.##0,00', 'euro')" />

                          </xsl:if>

                        </td>

                        <td  class="Ritenuta" >
                          <xsl:if test="Ritenuta">

                            <xsl:value-of select="Ritenuta" />

                          </xsl:if>
                        </td>

                        <td class="import" >

                          <xsl:choose>
                            <xsl:when test="Natura">

                              <xsl:value-of select="Natura" />

                            </xsl:when>
                            <xsl:otherwise>
                              <xsl:if test="AliquotaIVA">

                                <xsl:value-of select="format-number(AliquotaIVA,  '###.###.##0,00', 'euro')" />

                              </xsl:if>
                            </xsl:otherwise>
                          </xsl:choose>

                        </td>

                        <td class="import">
                          <xsl:if test="ImportoContributoCassa">

                            <xsl:value-of select="format-number(ImportoContributoCassa,  '###.###.##0,00', 'euro')" />

                          </xsl:if>

                        </td>

                      </tr>

                    </xsl:for-each>


                  </tbody>
                </table>


              </xsl:if>
              <!--  Fine Cassa Prevvidenziale    -->


              <div class="separa" > </div>
              <!-- Dati RIEPILOGO-->

              <table class="tbTitolo">
                <thead>
                  <tr>
                    <th>RIEPILOGHI IVA E TOTALI</th>
                  </tr>
                </thead>
              </table>



              <table class="tbFoglio">
                <thead>
                  <tr >

                    <th colspan="3" >esigibilità iva / riferimenti normativi</th>
                    <th class="perc">%IVA</th>
                    <th>Spese accessorie</th>
										<th class="perc">Arr.</th>
                    <th colspan="2" >Totale imponibile</th>
                    <th colspan="3" >Totale imposta</th>
                  </tr>
                </thead>
                <tbody>

                  <xsl:for-each select="DatiBeniServizi/DatiRiepilogo" >

										<!-- MKT VISUALIZZO ANCHE SE ZERO EURO DI IMPOBIBILE-->
                    <!--<xsl:if test="number(ImponibileImporto)">-->
                      <tr>
                        <td colspan="3" >
                          <xsl:choose>
                            <xsl:when test="EsigibilitaIVA">

                              <span>
                                <xsl:value-of select="EsigibilitaIVA" />
                              </span>
                              <xsl:variable name="EI">
                                <xsl:value-of select="EsigibilitaIVA" />
                              </xsl:variable>
                              <xsl:choose>
                                <xsl:when test="$EI='I'">
                                  (esigibilità immediata)
                                </xsl:when>
                                <xsl:when test="$EI='D'">
                                  (esigibilità differita)
                                </xsl:when>
                                <xsl:when test="$EI='S'">
                                  (scissione dei pagamenti)
                                </xsl:when>
                                <xsl:otherwise>
                                  <span>(!!! codice non previsto !!!)</span>
                                </xsl:otherwise>
                              </xsl:choose>
                            </xsl:when>

                            <xsl:otherwise>
															
															<xsl:choose>
																<xsl:when test="Natura">
																</xsl:when>
																<xsl:otherwise>
																	<span>Esigib. non dich. (si presume immediata)</span>
																</xsl:otherwise>
															</xsl:choose>

                            </xsl:otherwise>
                          </xsl:choose>

                          <xsl:if test="RiferimentoNormativo">
                            <div class="tx-xsmall">
                              <xsl:value-of select="RiferimentoNormativo" />
                            </div>
                          </xsl:if>
                        </td>


                        <td class="import" >

                          <xsl:call-template name="FormatIVA">
                            <xsl:with-param name="Natura" select="Natura" />
                            <xsl:with-param name="IVA" select="AliquotaIVA" />
                          </xsl:call-template>


                        </td>


                        <td class="import">

                          <xsl:if test="SpeseAccessorie">
                            <xsl:value-of select="format-number(SpeseAccessorie,  '###.###.##0,00', 'euro')" />
                          </xsl:if>
                        </td>


						 <td class="import">

                        <xsl:if test="Arrotondamento">
                          <xsl:value-of select="format-number(Arrotondamento,  '###.###.##0,00', 'euro')" />
                        </xsl:if>
                      </td>
                        <td  colspan="2" class="import" >

                          <xsl:if test="ImponibileImporto">
                            <xsl:value-of select="format-number(ImponibileImporto,  '###.###.##0,00', 'euro')" />
                          </xsl:if>
                        </td>

                        <td colspan="3"  class="import" >

                          <xsl:if test="Imposta">

                            <xsl:choose>
                              <xsl:when test="Imposta = 0">
                                <xsl:text>0</xsl:text>
                              </xsl:when>
                              <xsl:otherwise>
                                <xsl:value-of select="format-number(Imposta,  '###.###.##0,00', 'euro')" />
                              </xsl:otherwise>
                            </xsl:choose>



                          </xsl:if>
                        </td>

                      </tr>

                    <!--</xsl:if>-->

                  </xsl:for-each>

                  <!-- Importo Totale  -->
                  <tr >

                    <th  colspan="2">
                      Importo bollo
                    </th>
                    <th  colspan="3">
                      Sconto/Maggiorazione
                    </th>



										<th class="perc">Arr.</th>
										<th>Valuta</th>
                    <th colspan="4" >
                      Totale documento
                    </th>


                  </tr>

                  <tr >
                    <td colspan="2" class="import" >
											<xsl:if test="DatiGenerali/DatiGeneraliDocumento/DatiBollo/BolloVirtuale">
												<xsl:value-of select="DatiGenerali/DatiGeneraliDocumento/DatiBollo/BolloVirtuale" />
												<xsl:text> </xsl:text>
											</xsl:if>
                      <xsl:if test="DatiGenerali/DatiGeneraliDocumento/DatiBollo/ImportoBollo">

                        <xsl:value-of select="format-number(DatiGenerali/DatiGeneraliDocumento/DatiBollo/ImportoBollo,  '###.###.##0,00', 'euro')" />

                      </xsl:if>
                    </td>
                    <td colspan="3" class="import">
                      <xsl:for-each select="DatiGenerali/DatiGeneraliDocumento/ScontoMaggiorazione"  >

                        <xsl:call-template name="FormatSconto" >
                          <xsl:with-param name="tipo" select="Tipo" />
                          <xsl:with-param name="percentuale" select="Percentuale" />
                          <xsl:with-param name="importo" select="Importo" />
                        </xsl:call-template>


                      </xsl:for-each>
                    </td>


                    
					
                    <td class="import">

                      <xsl:if test="DatiGenerali/DatiGeneraliDocumento/Arrotondamento">

                        <xsl:value-of select="format-number(DatiGenerali/DatiGeneraliDocumento/Arrotondamento,  '###.###.##0,00', 'euro')" />

                      </xsl:if>
                    </td>
										<td class="textCenter"  >

                      <xsl:if test="DatiGenerali/DatiGeneraliDocumento/Divisa">

                        <xsl:value-of select="DatiGenerali/DatiGeneraliDocumento/Divisa" />

                      </xsl:if>
                    </td>
                    <td colspan="4" class="import">

                      <xsl:if test="DatiGenerali/DatiGeneraliDocumento/ImportoTotaleDocumento">

                        <xsl:value-of select="format-number(DatiGenerali/DatiGeneraliDocumento/ImportoTotaleDocumento,  '###.###.##0,00', 'euro')" />

                      </xsl:if>
                    </td>

                  </tr>

                  <!-- FINE Importo Totale  -->
                </tbody>
              </table>
              <!--  FINE Dettaglio Linee   -->


              <!--   Dati Ritenuta Acconto   -->
              <xsl:if test="DatiGenerali/DatiGeneraliDocumento/DatiRitenuta">
                <div class="separa"> </div>
				
				   <table class="tbFoglio">

					  <thead>
						<tr>
						  <th class="title"> Dati ritenuta d'acconto</th>
						  <th class="perc">Aliquota ritenuta</th>
						  <th>Causale	</th>
						  <th width="15%">Importo </th>
						</tr>
					  </thead>
					  <tbody>
					  
					   <xsl:for-each select="DatiGenerali/DatiGeneraliDocumento/DatiRitenuta"  >
							<xsl:apply-templates select="." />
						</xsl:for-each>
					  </tbody>
					</table>				
                
              </xsl:if>
              <!--  Fine Dati Ritenuta   -->


              <div class="separa"> </div>


              <!--   Dati Pagamento   -->

              <table class="tbFoglio" >
                <thead>
                  <tr>
                    <th>Modalità pagamento</th>
                    <th>IBAN</th>
                    <th>Istituto</th>
                    <th class="data">Data scadenza</th>
                    <th class="ximport">Importo</th>
                  </tr>
                </thead>
                <tbody>
                  <xsl:for-each select="DatiPagamento" >

                    <xsl:for-each select="DettaglioPagamento">

                      <tr>
                        <td>

                          <xsl:if test="ModalitaPagamento">
                            <span>
                              <xsl:value-of select="ModalitaPagamento" />
                            </span>
                            <xsl:variable name="MP">
                              <xsl:value-of select="ModalitaPagamento" />
                            </xsl:variable>
                            <xsl:choose>
                              <xsl:when test="$MP='MP01'">
                                Contanti
                              </xsl:when>
                              <xsl:when test="$MP='MP02'">
                                Assegno
                              </xsl:when>
                              <xsl:when test="$MP='MP03'">
                                Assegno circolare
                              </xsl:when>
                              <xsl:when test="$MP='MP04'">
                                Contanti presso Tesoreria
                              </xsl:when>
                              <xsl:when test="$MP='MP05'">
                                Bonifico
                              </xsl:when>
                              <xsl:when test="$MP='MP06'">
                                Vaglia cambiario
                              </xsl:when>
                              <xsl:when test="$MP='MP07'">
                                Bollettino bancario
                              </xsl:when>
                              <xsl:when test="$MP='MP08'">
                                Carta di pagamento
                              </xsl:when>
                              <xsl:when test="$MP='MP09'">
                                RID
                              </xsl:when>
                              <xsl:when test="$MP='MP10'">
                                RID utenze
                              </xsl:when>
                              <xsl:when test="$MP='MP11'">
                                RID veloce
                              </xsl:when>
                              <xsl:when test="$MP='MP12'">
                                RIBA
                              </xsl:when>
                              <xsl:when test="$MP='MP13'">
                                MAV
                              </xsl:when>
                              <xsl:when test="$MP='MP14'">
                                Quietanza erario
                              </xsl:when>
                              <xsl:when test="$MP='MP15'">
                                Giroconto su conti di contabilità speciale
                              </xsl:when>
                              <xsl:when test="$MP='MP16'">
                                Domiciliazione bancaria
                              </xsl:when>
                              <xsl:when test="$MP='MP17'">
                                Domiciliazione postale
                              </xsl:when>
                              <xsl:when test="$MP='MP18'">
                                Bollettino di c/c postale
                              </xsl:when>
                              <xsl:when test="$MP='MP19'">
                                SEPA Direct Debit
                              </xsl:when>
                              <xsl:when test="$MP='MP20'">
                                SEPA Direct Debit CORE
                              </xsl:when>
                              <xsl:when test="$MP='MP21'">
                                SEPA Direct Debit B2B
                              </xsl:when>
                              <xsl:when test="$MP='MP22'">
                                Trattenuta su somme già riscosse
                              </xsl:when>
							  <xsl:when test="$MP='MP23'">
								  PagoPA
								</xsl:when>
                              <xsl:when test="$MP=''">
                              </xsl:when>
                              <xsl:otherwise>
                                <span></span>
                              </xsl:otherwise>
                            </xsl:choose>
                            <span>
                              <xsl:value-of select="OpzDescrizionePagamento" />
                            </span>
                          </xsl:if>
													<xsl:if test="Beneficiario">
														<div class="tx-xsmall">
														Beneficiario: <xsl:value-of select="Beneficiario" />
														</div>
													</xsl:if>
													<xsl:if test="DataRiferimentoTerminiPagamento">
														<div class="tx-xsmall">
														Data Rif. Termini Pag.: 
															<xsl:call-template name="FormatDateIta">
																<xsl:with-param name="DateTime" select="DataRiferimentoTerminiPagamento" /> 
															</xsl:call-template>
														</div>
													</xsl:if>
													<xsl:if test="GiorniTerminiPagamento">
														<div class="tx-xsmall">
														Giorni Termini Pag.: <xsl:value-of select="GiorniTerminiPagamento" />
														</div>
													</xsl:if>
													<xsl:if test="CodUfficioPostale">
														<div class="tx-xsmall">
														Cod. ufficio postale: <xsl:value-of select="CodUfficioPostale" />
														</div>
													</xsl:if>
													<xsl:if test="CognomeQuietanzante | NomeQuietanzante | TitoloQuietanzante">
														<div class="tx-xsmall">
															Quietanzante: 
															<xsl:if test="TitoloQuietanzante">
																<xsl:value-of select="TitoloQuietanzante" />
																<xsl:text> </xsl:text> 
															</xsl:if>
															<xsl:if test="CognomeQuietanzante">
																<xsl:value-of select="CognomeQuietanzante" />
																<xsl:text> </xsl:text>
															</xsl:if>
															<xsl:if test="NomeQuietanzante">
																<xsl:value-of select="NomeQuietanzante" />
															</xsl:if>
														</div>
													</xsl:if>
													<xsl:if test="CFQuietanzante">
														<div class="tx-xsmall">
														CF Quietanzante: <xsl:value-of select="CFQuietanzante" />
														</div>
													</xsl:if>
													<xsl:if test="ABI | CAB | BIC">
														<div class="tx-xsmall">
															<xsl:if test="ABI">
																ABI: <xsl:value-of select="ABI" />
																<xsl:text> </xsl:text>
															</xsl:if>
															<xsl:if test="CAB">
																CAB: <xsl:value-of select="CAB" />
																<xsl:text> </xsl:text>
															</xsl:if>
															<xsl:if test="BIC">
																BIC: <xsl:value-of select="BIC" />
															</xsl:if>
														</div>
													</xsl:if>
													<xsl:if test="ScontoPagamentoAnticipato">
														<div class="tx-xsmall">
														Sconto Pagamento Anticipato:  <xsl:value-of select="format-number(ScontoPagamentoAnticipato,  '###.###.##0,00', 'euro')" />
														</div>
													</xsl:if>
													<xsl:if test="DataLimitePagamentoAnticipato">
														<div class="tx-xsmall">
														Limite Pag. Anticipato: 
															<xsl:call-template name="FormatDateIta">
																<xsl:with-param name="DateTime" select="DataLimitePagamentoAnticipato" /> 
															</xsl:call-template>
														</div>
													</xsl:if>
													<xsl:if test="PenalitaPagamentiRitardati">
														<div class="tx-xsmall">
														Pen. Pag. Ritardati: <xsl:value-of select="format-number(PenalitaPagamentiRitardati,  '###.###.##0,00', 'euro')" />
														</div>
													</xsl:if>
													<xsl:if test="DataDecorrenzaPenale">
														<div class="tx-xsmall">
														Decorrenza Penale: 
															<xsl:call-template name="FormatDateIta">
																<xsl:with-param name="DateTime" select="DataDecorrenzaPenale" /> 
															</xsl:call-template>
														</div>
													</xsl:if>
													<xsl:if test="CodicePagamento">
														<div class="tx-xsmall">
														Cod. Pagamento: <xsl:value-of select="CodicePagamento" />
														</div>
													</xsl:if>
												</td>

                        <td>

                          <xsl:if test="IBAN">

                            <xsl:value-of select="IBAN" />

                          </xsl:if>
                        </td>

                        <td>

                          <xsl:if test="IstitutoFinanziario">
                            <xsl:value-of select="IstitutoFinanziario" />
                          </xsl:if>
                        </td>

                        <td class="data">
                          <xsl:if test="DataScadenzaPagamento">
                            <xsl:call-template name="FormatDateIta">
                              <xsl:with-param name="DateTime" select="DataScadenzaPagamento" />
                            </xsl:call-template>
                          </xsl:if>
                        </td>
                        <td class="import">

                          <xsl:if test="ImportoPagamento">

                            <xsl:value-of select="format-number(ImportoPagamento,  '###.###.##0,00', 'euro')" />

                          </xsl:if>
                        </td>
                      </tr>
                    </xsl:for-each>

                  </xsl:for-each>
                </tbody>
              </table>
              <!-- FINE   Dati Pagamento   -->

              <div style="height:10px" > </div>

              <xsl:for-each select="OpzRiepilogoIVA"  >
                <div class="tx-xsmall">
                  * <xsl:value-of select="." />
                </div>

              </xsl:for-each>
              <xsl:if test="OpzRiepilogoIVA">
                <div style="height:10px" > </div>
              </xsl:if>

            </xsl:otherwise>

          </xsl:choose>

					
          <!-- Definizione degli allegati -->
          <xsl:if test="Allegati">
						
						<div class="tx-small" >Allegati: <small>(attenzione: se il link non funziona si consiglia di riprovare con Google Chrome o Firefox. Non funziona da stampa PDF)</small></div>

            <ul class="ulAllegati">
              <xsl:for-each select="Allegati">
								<xsl:variable name="_nome" select="NomeAttachment"></xsl:variable>
								<xsl:variable name="_stream" select="Attachment"></xsl:variable>
								<xsl:variable name="_compressione" select="AlgoritmoCompressione"></xsl:variable>
								<xsl:variable name="_formato" select="FormatoAttachment"></xsl:variable>
								
                <li>
                  <div class="tx-small">
										<xsl:if test="NomeAttachment">
											
												Nome dell'allegato:
												<xsl:if test="not(AlgoritmoCompressione)">
													<span>
														<xsl:if test="contains($_nome, '.')">
															<a download="{$_nome}" href='data:application/octet-stream;base64,{$_stream}'><xsl:value-of select="NomeAttachment" /></a>	
														</xsl:if>
														<xsl:if test="not(contains($_nome, '.'))">
															<a download="{$_nome}.{$_formato}" href='data:application/octet-stream;base64,{$_stream}'><xsl:value-of select="NomeAttachment" /></a>
														</xsl:if>	
													</span>
												</xsl:if>
												<xsl:if test="AlgoritmoCompressione">
													<span>
														<a download="{$_nome}.{$_compressione}" href='data:application/octet-stream;base64,{$_stream}'><xsl:value-of select="NomeAttachment" /></a>
													</span>
												</xsl:if>
										</xsl:if>
                    
                    <xsl:text>  - </xsl:text>
										
                    <xsl:value-of select="DescrizioneAttachment" />
                  </div>
                </li>


              </xsl:for-each>

            </ul>

          </xsl:if>
					
          <!--Definizione se fattura è AssoSofware-->

          <xsl:if test="$posASWRELSTD &gt; 0 ">
            <div class="dtASWRELSTD">

              <label class="headerLabel">Conforme Standard AssoSoftware</label>

            </div>

          </xsl:if>


          <!-- FINE    ASWRELSTD  -->
					
					<!--INIZIO DATI  TRASPORTO-->
					<xsl:if test="DatiGenerali/DatiTrasporto">
						<div id="dati-trasporto">
							<h3>Dati relativi al trasporto</h3>

							<xsl:if test="DatiGenerali/DatiTrasporto/DatiAnagraficiVettore">
								<h4>Dati del vettore</h4>

								<ul>
									<xsl:for-each select="DatiGenerali/DatiTrasporto/DatiAnagraficiVettore">
										<xsl:if test="IdFiscaleIVA/IdPaese">
											<li>
												Identificativo fiscale ai fini IVA:
												<span>
													<xsl:value-of select="IdFiscaleIVA/IdPaese" />
													<xsl:value-of select="IdFiscaleIVA/IdCodice" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="CodiceFiscale">
											<li>
												Codice Fiscale:
												<span>
													<xsl:value-of select="CodiceFiscale" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="Anagrafica/Denominazione">
											<li>
												Denominazione:
												<span>
													<xsl:value-of select="Anagrafica/Denominazione" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="Anagrafica/Nome">
											<li>
												Nome:
												<span>
													<xsl:value-of select="Anagrafica/Nome" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="Anagrafica/Cognome">
											<li>
												Cognome:
												<span>
													<xsl:value-of select="Anagrafica/Cognome" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="Anagrafica/Titolo">
											<li>
												Titolo onorifico:
												<span>
													<xsl:value-of select="Anagrafica/Titolo" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="Anagrafica/CodEORI">
											<li>
												Codice EORI:
												<span>
													<xsl:value-of select="Anagrafica/CodEORI" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="NumeroLicenzaGuida">
											<li>
												Numero licenza di guida:
												<span>
													<xsl:value-of select="NumeroLicenzaGuida" />
												</span>
											</li>
										</xsl:if>
									</xsl:for-each>
								</ul>
							</xsl:if>

							<xsl:if test="DatiGenerali/DatiTrasporto/MezzoTrasporto | DatiGenerali/DatiTrasporto/CausaleTrasporto | DatiGenerali/DatiTrasporto/NumeroColli | DatiGenerali/DatiTrasporto/Descrizione | DatiGenerali/DatiTrasporto/UnitaMisuraPeso | DatiGenerali/DatiTrasporto/PesoLordo | DatiGenerali/DatiTrasporto/PesoNetto | DatiGenerali/DatiTrasporto/DataOraRitiro | DatiGenerali/DatiTrasporto/DataInizioTrasporto | DatiGenerali/DatiTrasporto/TipoResa | DatiGenerali/DatiTrasporto/IndirizzoResa">
								<h4>Altri dati</h4>

								<ul>
									<xsl:for-each select="DatiGenerali/DatiTrasporto">
										<xsl:if test="MezzoTrasporto">
											<li>
												Mezzo di trasporto:
												<span>
													<xsl:value-of select="MezzoTrasporto" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="CausaleTrasporto">
											<li>
												Causale trasporto:
												<span>
													<xsl:value-of select="CausaleTrasporto" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="NumeroColli">
											<li>
												Numero colli trasportati:
												<span>
													<xsl:value-of select="NumeroColli" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="Descrizione">
											<li>
												Descrizione beni trasportati:
												<span>
													<xsl:value-of select="Descrizione" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="UnitaMisuraPeso">
											<li>
												Unit&#224; di misura del peso merce:
												<span>
													<xsl:value-of select="UnitaMisuraPeso" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="PesoLordo">
											<li>
												Peso lordo:
												<span>
													<xsl:value-of select="PesoLordo" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="PesoNetto">
											<li>
												Peso netto:
												<span>
													<xsl:value-of select="PesoNetto" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="DataOraRitiro">
											<li>
												Data e ora ritiro merce:
												<span>
													<xsl:value-of select="DataOraRitiro" />
												</span>
												<xsl:call-template name="FormatDateIta">
													<xsl:with-param name="DateTime" select="DataOraRitiro" />
												</xsl:call-template>
											</li>
										</xsl:if>
										<xsl:if test="DataInizioTrasporto">
											<li>
												Data inizio trasporto:
												<span>
													<xsl:value-of select="DataInizioTrasporto" />
												</span>
												
											</li>
										</xsl:if>
										<xsl:if test="TipoResa">
											<li>
												Tipologia di resa:
												<span>
													<xsl:value-of select="TipoResa" />
												</span>

												(codifica secondo standard ICC)
											</li>
										</xsl:if>
										<xsl:if test="IndirizzoResa/Indirizzo">
											<li>
												Indirizzo di resa:
												<span>
													<xsl:value-of select="IndirizzoResa/Indirizzo" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="IndirizzoResa/NumeroCivico">
											<li>
												Numero civico indirizzo di resa:
												<span>
													<xsl:value-of select="IndirizzoResa/NumeroCivico" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="IndirizzoResa/CAP">
											<li>
												CAP indirizzo di resa:
												<span>
													<xsl:value-of select="IndirizzoResa/CAP" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="IndirizzoResa/Comune">
											<li>
												Comune di resa:
												<span>
													<xsl:value-of select="IndirizzoResa/Comune" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="IndirizzoResa/Provincia">
											<li>
												Provincia di resa:
												<span>
													<xsl:value-of select="IndirizzoResa/Provincia" />
												</span>
											</li>
										</xsl:if>
										<xsl:if test="IndirizzoResa/Nazione">
											<li>
												Nazione di resa:
												<span>
													<xsl:value-of select="IndirizzoResa/Nazione" />
												</span>
											</li>
										</xsl:if>
									</xsl:for-each>
								</ul>
							</xsl:if>

						</div>
					</xsl:if>
					<!--FINE DATI TRASPORTO-->

					<!--INIZIO FATTURA PRINCIPALE-->
					<xsl:if test="DatiGenerali/FatturaPrincipale">
						<div id="fattura-principale">
							<h3>Dati relativi alla fattura principale</h3>
							<ul>
								<xsl:if test="DatiGenerali/FatturaPrincipale/NumeroFatturaPrincipale">
									<li>
										Numero fattura principale:
										<span>
											<xsl:value-of select="DatiGenerali/FatturaPrincipale/NumeroFatturaPrincipale" />
										</span>
									</li>
								</xsl:if>
								<xsl:if test="DatiGenerali/FatturaPrincipale/DataFatturaPrincipale">
									<li>
										Data fattura principale:
										<span>
											<xsl:value-of select="DatiGenerali/FatturaPrincipale/DataFatturaPrincipale" />
										</span>
										<xsl:call-template name="FormatDateIta">
											<xsl:with-param name="DateTime" select="DatiGenerali/FatturaPrincipale/DataFatturaPrincipale" />
										</xsl:call-template>
									</li>
								</xsl:if>
							</ul>
						</div>
					</xsl:if>
					<!--FINE FATTURA PRINCIPALE-->
					
					<!--INIZIO DATI VEICOLI-->
					<xsl:if test="DatiVeicoli">
						<div id="dati-veicoli">
							<h3>Dati Veicoli ex art. 38 dl 331/1993</h3>
							<ul>
								<xsl:for-each select="DatiVeicoli">
									<xsl:if test="Data">
										<li>
											Data prima immatricolazione / iscrizione PR:
											<span>
												<xsl:value-of select="Data" />
											</span>
											<xsl:call-template name="FormatDateIta">
												<xsl:with-param name="DateTime" select="Data" />
											</xsl:call-template>
										</li>
									</xsl:if>
									<xsl:if test="TotalePercorso">
										<li>
											Totale percorso:
											<span>
												<xsl:value-of select="TotalePercorso" />
											</span>
										</li>
									</xsl:if>
								</xsl:for-each>
							</ul>
						</div>
					</xsl:if>
					<!--FINE DATI VEICOLI-->

        </xsl:for-each>
        <!--FINE BODY-->
				
				<div style="font-size:0.8em;">
					<xsl:for-each select="$TipoFattura/FatturaElettronicaHeader/DatiTrasmissione">
						<br/><br/>
						<xsl:if test="IdTrasmittente">
							Trasmittente: 
							<xsl:value-of select="IdTrasmittente/IdPaese" />
							<xsl:value-of select="IdTrasmittente/IdCodice" />	
							<xls-text> / </xls-text>
						</xsl:if>
						<xsl:if test="ProgressivoInvio">
							Progressivo: 
							<xsl:value-of select="ProgressivoInvio" />	
							<xls-text> / </xls-text>
						</xsl:if>
						<xsl:if test="FormatoTrasmissione">
							Formato Trasmissione: 
							<xsl:value-of select="FormatoTrasmissione" />	
							<xls-text> / </xls-text>
						</xsl:if>
						<xsl:if test="ContattiTrasmittente/Telefono | ContattiTrasmittente/Email">
							<xsl:if test="ContattiTrasmittente/Telefono">
									Tel: <xsl:value-of select="ContattiTrasmittente/Telefono" />	
									<xls-text> / </xls-text>
							</xsl:if>
							<xsl:if test="ContattiTrasmittente/Email">
									Email: <xsl:value-of select="ContattiTrasmittente/Email" />	
									<xls-text> / </xls-text>
							</xsl:if>	
						</xsl:if>
					</xsl:for-each>
				</div>
					
      </div>
				


    </xsl:if>
  </xsl:template>

  <xsl:template match="/">
    <html>
      <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <style type="text/css">

          #fattura-elettronica
          {
          font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
          margin-left: auto;
          margin-right: auto;
          max-width: 800px;
          width: 100%;
          padding: 0;  }

          #fattura-elettronica
          div.page {
          }

          .tbHeader
          {
          max-width: 800px;
					width: 100%;
          border: 2px solid black;
          }
					

					tr td {vertical-align: top;}

          .tdHead {
          width: 50%;
          height: 91px;
          border: 1px solid black;
          }

          .tableHead
          {
          font-size:smaller;
          width: 100%;
          }

          .headBorder
          {
          <!--border: 2px solid black;
			width:100%; 
			height: 210px;
			border-bottom-left-radius:30px;
			border-bottom-right-radius:30px; -->
          }

          .headerLabel
          {
          color:#282828;
          font-weight:bold;


          }

          .headContent
          {
          margin-left:10px;
          margin-bottom:0px
          }

          .mt5
          {
          margin-top:5px
          }


          tr.break { page-break-after: always; }

          .ulAllegati
          {
          margin-top:0px;
          }


          .separa
          {
          height:20px;
          }

          table.tbTitolo
          {
          max-width: 800px;
					width: 100%;
          table-layout: fixed;
          border-collapse: collapse;
          word-wrap:normal; <!--break-word;-->
          }
          table.tbTitolo th {
          padding-left: 5px;
          padding-right: 5px;
          border: solid 1px #000000;
          border-bottom: none;
          background-color: #747b7b;
          font-size:x-small;

          }


          table.tbFoglio
          {
          max-width: 800px;
					width: 100%;
          table-layout: fixed;
          border-collapse: collapse;
          word-wrap:break-word;
          }
		  
          table.tbFoglio th {
          padding-left: 5px;
          padding-right: 5px;
          border: solid 1px #000000;
          background-color: LightGrey;

          <!-- text-transform: uppercase; -->
          font-size:x-small;


          }

          table.tbFoglio tbody
          {
          border: solid 1px #000000;
          }

          table.tbFoglio th .perc
          {
          width:50px;
          }

          table.tbFoglio td {
          font-size:small;

          border-right: solid 1px #000000;
          border-bottom: none;
          border-left: solid 1px #000000;
          }

          table.tbFoglio tr {


          }

          .textRight
          {
          text-align:right;
          }

          .textCenter
          {
          text-align:center;
          }

          .textPerc
          {
          width:50px;
          }

          td.Ritenuta
          {
          width:50px;
          text-align:center;
          }
					
          th.title, .title td {
          width:48%
          }

          th.perc {
          width:50px;
          }

          th.perc2 {
          width:40px;
          }

          th.data {
          width:80px;
          }
					
          th.import
          {
          width:100px;
          }

          td.import
          {
          text-align:right;
          }
					
					th.descr {
					width:40%;
					}
					
					th.cod 
					{
					width:80px;
					}

          th.import2
          {
          width:80px;
          }

          td.import2
          {
          text-align:right;
          }

          th.ximport
          {
          width:100px;
          }

          td.ximport
          {
          text-align:center;
          }
          
          th.ximport2
          {
          width:80px;
          }

          td.ximport2
          {
          text-align:center;
          }

          td.data
          {
          text-align:center;
          }

          .tx-xsmall {
          font-size:x-small;
          }

          .tx-small {
          font-size:small;
          }

          .import
          {
          text-align:right;
          }
					

        </style>
      </head>
      <body>
				<xsl:if test="a:FatturaElettronica/errori">
					<div id="errori">
						<h3>Errori</h3>
							<ul>					
							<xsl:for-each select="a:FatturaElettronica/errori/errore">
								<li>
									<span>
										<xsl:value-of select="current()" />
									</span>
								</li>
							</xsl:for-each>
							</ul>
					</div>
				</xsl:if>
        <div id="fattura-container">

          <xsl:choose>
            <xsl:when test="d:FatturaElettronicaSemplificata">
              <!--versione 1.0 SEMPLIFICATA-->
              <xsl:call-template name="FatturaElettronica">
                <xsl:with-param name="TipoFattura" select="d:FatturaElettronicaSemplificata" />
                <xsl:with-param name="IsFPRS" select="1" />
              </xsl:call-template>
            </xsl:when>
            <xsl:when test="c:FatturaElettronica">
              <!--versione 1.0-->
              <xsl:call-template name="FatturaElettronica">
                <xsl:with-param name="TipoFattura" select="c:FatturaElettronica" />
                <xsl:with-param name="IsFPRS" select="0" />
              </xsl:call-template>
            </xsl:when>
            <xsl:when test="b:FatturaElettronica">
              <!--versione 1.1-->
              <xsl:call-template name="FatturaElettronica">
                <xsl:with-param name="TipoFattura" select="b:FatturaElettronica" />
                <xsl:with-param name="IsFPRS" select="0" />
              </xsl:call-template>
            </xsl:when>
            <xsl:otherwise>
              <xsl:call-template name="FatturaElettronica">
                <!--versione 1.2-->
                <xsl:with-param name="TipoFattura" select="a:FatturaElettronica" />
                <xsl:with-param name="IsFPRS" select="0" />
              </xsl:call-template>
            </xsl:otherwise>
          </xsl:choose>



				</div>
      </body>
		</html>
  </xsl:template>
</xsl:stylesheet>