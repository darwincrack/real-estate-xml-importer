jQuery(document).ready(function($) {
    var finished = false;

    console.log("finished", finished );
    // Confirmación antes de eliminar una propiedad
    $('.reip-delete-property').on('click', function(e) {
        if (!confirm('¿Estás seguro de que deseas eliminar esta propiedad?')) {
            e.preventDefault();
        }
    });
    
    // Manejar el clic del botón en lugar del submit del formulario
    $('#start-import').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('¿Deseas iniciar la importación ahora?')) {
            return false;
        }

        console.log('Nonce disponible:', reipAjax.nonce);
        var selectedXmlType = $('input[name="xml_type"]:checked').val();

        var formData = new FormData();
        formData.append('action', 'reip_import_properties');
        formData.append('security', reipAjax.nonce);
        formData.append('max_properties', $('#max_properties').val());
        formData.append('max_images', $('#max_images').val());
        formData.append('xml_type', selectedXmlType);

        // Mostrar el div de progreso
        $('#import-progress').show().html('Iniciando importación...');
        // Iniciar la importación vía AJAX
        $.ajax({
            url: reipAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                console.log('Enviando petición AJAX:', {
                    url: this.url,
                    formData: Object.fromEntries(formData)
                });
                setTimeout(updateImportProgress, 8000);

            },
            success: function(response) {
                console.log('Respuesta exitosa:', response);
                if (response.success) {
                    finished = true;
                    // Mostrar las estadísticas cuando la importación termine
                    if (response.data && response.data.stats) {
                        const stats = response.data.stats;
                        jQuery('#import-progress').html(`
                            <h3>Importación completada</h3>
                            <div class="reip-stats">
                                <p>Propiedades añadidas: ${stats.added}</p>
                                <p>Propiedades actualizadas: ${stats.updated}</p>
                                <p>Propiedades eliminadas: ${stats.deleted}</p>
                            </div>
                        `);
                    }


                } else {
                    $('#import-progress').html('Error: ' + (response.data ? response.data.message : 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                console.log('Error detallado:', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    headers: xhr.getAllResponseHeaders()
                });
                $('#import-progress').html('Error en la conexión: ' + error + '<br>Estado: ' + status);
            }
        });
    });


    function updateImportProgress() {

        console.log('updateImportProgress');
        jQuery.ajax({
            url: reipAjax.ajaxurl,
            data: {
                action: 'check_import_progress'
            },
            success: function(response) {
                if (response.data) {
                    var progress = (response.data.current / response.data.total) * 100;
                    jQuery('#import-progress').html(
                        'Procesando: ' + response.data.current + ' de ' + response.data.total + 
                        ' propiedades (' + Math.round(progress) + '%)'
                    );
    
                    console.log(response.data.current, response.data.total);
                    if (parseInt(response.data.current) < parseInt(response.data.total) && !finished) {
                        console.log('updateImportProgress: ' + response.data.current + ' de ' + response.data.total);
                        setTimeout(updateImportProgress, 2000);
                    }else{
                        if(!finished){
                            jQuery('#import-progress').html(`Importación finalizada con ${response.data.current} de ${response.data.total} propiedades, por favor, espere el resumen...`);
                        }
                    }
                }
            }
        });
    } 
    
    
    
    
    
    
    
    
    
    
    
        // Manejar el clic del botón en lugar del submit del formulario
    $('#save_ref_now').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('¿Deseas guardar?')) {
            return false;
        }


        var formData = new FormData();
        formData.append('action', 'reip_ref_properties');
        formData.append('security', reipAjax.nonce);
        formData.append('excluir_ref', $('#excluir_ref').val());
        formData.append('incluir_ref', $('#incluir_ref').val());


        // Mostrar el div de progreso
        $('#info-text').show().html('Por favor espere...');
        // Iniciar la importación vía AJAX
        $.ajax({
            url: reipAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function(xhr) {
                console.log('Enviando petición AJAX:', {
                    url: this.url,
                    formData: Object.fromEntries(formData)
                });
                setTimeout(updateImportProgress, 8000);

            },
            success: function(response) {
                console.log('Respuesta exitosa:', response);
                if (response.success) {
                        jQuery('#info-text').html(`<h3>Guardado con éxito!</h3>`);
 
                } else {
                    $('#info-text').html('Error: ' + (response.data ? response.data.message : 'Error desconocido'));
                }
            },
            error: function(xhr, status, error) {
                console.log('Error detallado:', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    headers: xhr.getAllResponseHeaders()
                });
                $('#info-text').html('Error en la conexión: ' + error + '<br>Estado: ' + status);
            }
        });
    });

    
    
    
    
    
    
    
    
    
    
    
});

