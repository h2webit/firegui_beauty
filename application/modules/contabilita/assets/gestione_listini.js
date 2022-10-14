$(() => {
    applicaListino = function (data, tipo_documento) { //Questa funzione sovrascrive quella nativa (che non fa niente) e serve proprio a gestire scadenze custom basate sulla configurazione cliente
        //In base all'id cliente, ai dati sul prodotto ecc, calcolo il prezzo di vendita
        //PF,IV,SC,PA,PP,PB,PV,RP,SP
        //console.log(data);
        var entita_cliente = $('[name="dest_entity_name"]').val();
        var listino_id = false;
        //console.log(cliente_raw_data);
        //alert(1);

        //Cerco il campo
        for (var campo in cliente_raw_data) {
            if (campo.includes("listino")) {
                listino_id = cliente_raw_data[campo];
                break;
            }
        }

        if (listino_id) {
            for (var i in listini) { //Ciclo l'array dei listini (configurate a monte)
                var listino = listini[i];
                if (listino.listini_id == listino_id) { //Trovo quello il cui codice coincide
                    $('#js_listino_applicato').html('Listino applicato: <strong>' + listino.listini_codice + '</strong>');
                    $('#js_listino_applicato').css('background-color', listino.listini_colore);
                }
            }
            if (data == false || tipo_documento == false) {
                return false;
            } else {
                for (var i in data) {
                    //Casto tutto a float per evitare che mi arrivino stringhe
                    data[i] = parseFloat(data[i]);
                }

                //Cerco il listino
                for (var i in listini) { //Ciclo l'array dei listini (configurate a monte)
                    var listino = listini[i];
                    if (listino.listini_id == listino_id) { //Trovo quello il cui codice coincide

                        var formula = listino.formule_formula;
                        var sconto = listino.listini_sconto;
                        console.log('Applico ' + listino.listini_nome + ' con formula ' + formula);
                        data.SC = parseFloat(sconto);
                        data.MA = parseFloat(sconto);
                        var formula_eval = formula.replaceAll('{', ' data.');
                        var formula_eval = formula_eval.replaceAll('}', ' ');

                        formula_eval = 'var data = this; data.PB = ' + formula_eval + '; return data';
                        console.log(formula_eval);
                        var result = new Function(formula_eval).call(data)
                        //console.log(result);
                        return data.PB;
                    }
                }
            }

        }

        if (tipo_documento == 6) { // tipo documento: ordine fornitore
            return data.PF
        } else {
            return data.PB;
        }
    }


});