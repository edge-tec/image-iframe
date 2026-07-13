/**
 * Image Frame Generator — Main Frontend Application
 * Handles Fabric.js canvas, drag & drop uploads, typography settings,
 * history states (undo/redo), and AJAX generation.
 */

$(document).ready(function() {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const baseUrl = $('meta[name="base-url"]').attr('content') || '';
    
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': csrfToken }
    });

    // ─── 1. INITIALIZE CANVAS ──────────────────────────────────────
    const canvas = new fabric.Canvas('editorCanvas', {
        width: 1080,
        height: 1080,
        backgroundColor: '#0a0e1a',
        preserveObjectStacking: true,
        selection: false
    });

    // Canvas object references
    let userImgObj = null;
    let frameImgObj = null;
    let logoImgObj = null;
    let headlineObj = null;
    let subheadingObj = null;
    let reporterObj = null;
    let dateObj = null;
    let timeObj = null;

    // Active configuration & state
    let currentConfig = null;
    let uploadedUserPath = '';
    let uploadedLogoPath = '';
    let isHDGenerating = false;

    // History Stack
    let history = [];
    let historyIndex = -1;
    let isHistoryAction = false;

    // ─── 2. DRAG & DROP UPLOADS ────────────────────────────────────

    function initDropZone(zoneId, inputId, labelId, uploadType, successCallback) {
        const zone = $(zoneId);
        const input = $(inputId);

        // Click to open file dialog
        zone.on('click', function(e) {
            if (e.target !== input[0]) input.trigger('click');
        });

        // File selected via dialog
        input.on('change', function() {
            handleFiles(this.files);
        });

        // Drag events
        zone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zone.addClass('dragover');
        });

        zone.on('dragleave dragend drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            zone.removeClass('dragover');
        });

        zone.on('drop', function(e) {
            const files = e.originalEvent.dataTransfer.files;
            input[0].files = files; // Sync input
            handleFiles(files);
        });

        function handleFiles(files) {
            if (!files || files.length === 0) return;
            const file = files[0];

            if (file.size > 10 * 1024 * 1024) {
                alert('File is too large. Max 10MB.');
                return;
            }

            $(labelId).text(file.name.length > 20 ? file.name.substring(0, 17) + '...' : file.name);
            zone.addClass('has-file');

            // We read locally for preview, but ALSO upload to server for HD generation
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                successCallback(dataUrl, file);
            };
            reader.readAsDataURL(file);
        }
    }

    // Photo Drop Zone
    initDropZone('#photoDropZone', '#userImageFile', '#photoFileName', 'image', function(dataUrl, file) {
        uploadFileAjax(file, 'image', function(path) {
            uploadedUserPath = path;
        });
        
        if (userImgObj) canvas.remove(userImgObj);
        
        fabric.Image.fromURL(dataUrl, function(img) {
            userImgObj = img;
            userImgObj.set({
                selectable: false, evented: false
            });
            centerAndScaleUserImage();
            canvas.add(userImgObj);
            userImgObj.sendToBack();
            canvas.renderAll();
            saveHistory();
        });
    });

    // Logo Drop Zone
    initDropZone('#logoDropZone', '#logoFile', '#logoFileName', 'logo', function(dataUrl, file) {
        uploadFileAjax(file, 'logo', function(path) {
            uploadedLogoPath = path;
        });

        if (logoImgObj) canvas.remove(logoImgObj);

        fabric.Image.fromURL(dataUrl, function(img) {
            logoImgObj = img;
            logoImgObj.set({
                selectable: false, evented: false
            });
            positionLogo();
            canvas.add(logoImgObj);
            if (frameImgObj) frameImgObj.bringToFront(); // keep frame on top
            canvas.renderAll();
            saveHistory();
        });
    });

    // AJAX Background Upload
    function uploadFileAjax(file, type, callback) {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);
        formData.append('csrf_token', csrfToken);

        $.ajax({
            url: baseUrl + '/ajax.php?action=upload',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(res) {
                if (res.status === 'success') {
                    callback(res.relative_path);
                } else {
                    console.error('Upload error:', res.message);
                }
            }
        });
    }

    // ─── 3. FRAME SELECTION & JSON PARSING ─────────────────────────

    $(document).on('click', '.frame-thumbnail', function() {
        $('.frame-thumbnail').removeClass('active');
        $(this).addClass('active');
        
        const overlaySrc = $(this).find('img').attr('src');

        // Parse JSON payload embedded in the HTML
        const jsonString = $(this).find('.frame-data-payload').html();
        try {
            currentConfig = JSON.parse(jsonString);
            if (!currentConfig.frame_image) currentConfig.frame_image = overlaySrc;
            applyFrameConfig(currentConfig, $(this).data('id'));
        } catch (e) {
            console.error('Invalid Frame JSON', e);
            alert('Error loading frame configuration.');
        }
    });

    function applyFrameConfig(config, dbId) {
        config.db_id = dbId;
        
        // Load Frame Overlay Image
        if (frameImgObj) canvas.remove(frameImgObj);
        
        // Convert paths properly
        let overlayPath = config.frame_image;
        if (overlayPath && !overlayPath.startsWith('http')) {
            // Remove baseUrl if it's already there to prevent double prepending, or just prepend if missing
            if (baseUrl && !overlayPath.startsWith(baseUrl)) {
                overlayPath = baseUrl + '/' + overlayPath;
            }
        }
        
        fabric.Image.fromURL(overlayPath, function(img) {
            frameImgObj = img;
            frameImgObj.set({
                left: 0, top: 0,
                width: 1080, height: 1080,
                selectable: false, evented: false
            });
            canvas.add(frameImgObj);
            frameImgObj.bringToFront();

            // Reposition existing elements based on new template
            if (userImgObj) centerAndScaleUserImage();
            if (logoImgObj) positionLogo();
            
            initTypography();
            
            canvas.renderAll();
            saveHistory();
        }, { crossOrigin: 'anonymous' });
    }

    function centerAndScaleUserImage() {
        if (!userImgObj || !currentConfig) return;
        const box = currentConfig.image;
        const scale = Math.max(box.width / userImgObj.width, box.height / userImgObj.height);
        
        userImgObj.set({
            originX: 'center', originY: 'center',
            left: box.x + (box.width / 2),
            top: box.y + (box.height / 2),
            scaleX: scale, scaleY: scale,
            angle: 0
        });
    }

    function positionLogo() {
        if (!logoImgObj || !currentConfig || !currentConfig.logo) return;
        const box = currentConfig.logo;
        const scale = Math.min(box.width / logoImgObj.width, box.height / logoImgObj.height);
        
        logoImgObj.set({
            left: box.x, top: box.y,
            scaleX: scale, scaleY: scale,
            cornerColor: '#10b981', transparentCorners: false
        });
    }

    // ─── 4. TYPOGRAPHY (Headline, Date, Time) ──────────────────────
    
    function initTypography() {
        if (headlineObj) canvas.remove(headlineObj);
        if (subheadingObj) canvas.remove(subheadingObj);
        if (reporterObj) canvas.remove(reporterObj);
        if (dateObj) canvas.remove(dateObj);
        if (timeObj) canvas.remove(timeObj);

        if (!currentConfig) return;

        // Headline
        if (currentConfig.headline) {
            const hc = currentConfig.headline;
            // Sync UI inputs
            $('#fontColorPicker').val(hc.color || '#ffffff');
            $('#fontSizeRange').val(hc.fontSize || 42);
            $('#fontSizeVal').text((hc.fontSize || 42) + 'px');
            
            headlineObj = new fabric.Textbox($('#headlineText').val() || '', {
                left: hc.x, top: hc.y,
                width: 800, originX: 'center', textAlign: 'center',
                fontSize: hc.fontSize || 42,
                fill: hc.color || '#ffffff',
                fontFamily: hc.font || 'Hind Siliguri',
                fontWeight: 'normal',
                cornerColor: '#a855f7', transparentCorners: false, borderColor: '#a855f7'
            });
            canvas.add(headlineObj);
        }

        // Subheading
        if (currentConfig.subheading) {
            const sc = currentConfig.subheading;
            subheadingObj = new fabric.Textbox($('#subheadingText').val() || '', {
                left: sc.x, top: sc.y,
                width: 600, originX: 'center', textAlign: 'center',
                fontSize: sc.fontSize || 30,
                fill: sc.color || '#3b82f6',
                fontFamily: sc.font || 'Hind Siliguri',
                fontWeight: 'normal',
                cornerColor: '#3b82f6', transparentCorners: false, borderColor: '#3b82f6'
            });
            canvas.add(subheadingObj);
        }

        // Reporter
        if (currentConfig.reporter) {
            const rc = currentConfig.reporter;
            reporterObj = new fabric.Textbox($('#reporterText').val() || '', {
                left: rc.x, top: rc.y,
                width: 600, originX: 'center', textAlign: 'center',
                fontSize: rc.fontSize || 30,
                fill: rc.color || '#14b8a6',
                fontFamily: rc.font || 'Hind Siliguri',
                fontWeight: 'normal',
                cornerColor: '#14b8a6', transparentCorners: false, borderColor: '#14b8a6'
            });
            canvas.add(reporterObj);
        }

        // Date & Time
        const sDate = $('#serverDate').val();
        const sTime = $('#serverTime').val();

        if (currentConfig.date) {
            dateObj = new fabric.Text(sDate, {
                left: currentConfig.date.x, top: currentConfig.date.y,
                fontSize: currentConfig.date.fontSize || 24,
                fill: currentConfig.date.color || '#ffffff',
                fontFamily: 'Roboto', fontWeight: 'bold',
                originX: 'center', selectable: false
            });
            canvas.add(dateObj);
        }

        if (currentConfig.time) {
            timeObj = new fabric.Text(sTime, {
                left: currentConfig.time.x, top: currentConfig.time.y,
                fontSize: currentConfig.time.fontSize || 24,
                fill: currentConfig.time.color || '#ffffff',
                fontFamily: 'Roboto', fontWeight: 'bold',
                originX: 'center', selectable: false
            });
            canvas.add(timeObj);
        }

        if (frameImgObj) frameImgObj.bringToFront();
    }

    // Bind UI Controls for Typography
    $('#headlineText').on('input', function() {
        if (headlineObj) {
            headlineObj.set({ text: $(this).val() });
            canvas.renderAll();
        }
    }).on('change', saveHistory);

    $('#subheadingText').on('input', function() {
        if (subheadingObj) {
            subheadingObj.set({ text: $(this).val() });
            canvas.renderAll();
        }
    }).on('change', saveHistory);

    $('#reporterText').on('input', function() {
        if (reporterObj) {
            reporterObj.set({ text: $(this).val() });
            canvas.renderAll();
        }
    }).on('change', saveHistory);

    $('#fontColorPicker').on('input', function() {
        const color = $(this).val();
        if (headlineObj) headlineObj.set({ fill: color });
        if (subheadingObj) subheadingObj.set({ fill: color });
        if (reporterObj) reporterObj.set({ fill: color });
        if (dateObj) dateObj.set({ fill: color });
        if (timeObj) timeObj.set({ fill: color });
        canvas.renderAll();
    });

    $('#fontSizeRange').on('input', function() {
        const size = parseInt($(this).val());
        $('#fontSizeVal').text(size + 'px');
        if (headlineObj) {
            headlineObj.set({ fontSize: size });
            canvas.renderAll();
        }
    }).on('change', saveHistory);

    $('#fontFamilySelect').on('change', function() {
        const font = $(this).val();
        if (headlineObj) headlineObj.set({ fontFamily: font });
        if (subheadingObj) subheadingObj.set({ fontFamily: font });
        if (reporterObj) reporterObj.set({ fontFamily: font });
        canvas.renderAll();
        saveHistory();
    });

    $('#btnBold').on('change', function() {
        if (headlineObj) {
            headlineObj.set({ fontWeight: this.checked ? 'bold' : 'normal' });
            canvas.renderAll();
            saveHistory();
        }
    });

    $('#btnShadow').on('change', function() {
        if (headlineObj) {
            if (this.checked) {
                headlineObj.set({ shadow: new fabric.Shadow({ color: 'rgba(0,0,0,0.6)', blur: 8, offsetX: 2, offsetY: 2 }) });
            } else {
                headlineObj.set({ shadow: null });
            }
            canvas.renderAll();
            saveHistory();
        }
    });

    $('.align-opt').on('click', function(e) {
        e.preventDefault();
        $('.align-opt').removeClass('active');
        $(this).addClass('active');
        const align = $(this).data('align');
        
        // Update Icon
        $('#alignIcon').attr('class', 'fa-solid fa-align-' + align);

        if (headlineObj) {
            headlineObj.set({ textAlign: align });
            canvas.renderAll();
            saveHistory();
        }
    });

    // ─── 6. HISTORY (UNDO/REDO) ────────────────────────────────────

    function saveHistory() {
        if (isHistoryAction) return;
        const state = JSON.stringify(canvas.toJSON(['selectable', 'evented']));
        history = history.slice(0, historyIndex + 1);
        history.push(state);
        historyIndex = history.length - 1;
        updateHistoryButtons();
    }

    function updateHistoryButtons() {
        $('#btnUndo').prop('disabled', historyIndex <= 0);
        $('#btnRedo').prop('disabled', historyIndex >= history.length - 1);
    }

    function loadHistory(idx) {
        isHistoryAction = true;
        canvas.loadFromJSON(history[idx], function() {
            // Re-bind object references
            const objs = canvas.getObjects();
            userImgObj = objs[0] || null; // background
            frameImgObj = objs[objs.length - 1] || null; // usually on top, though texts might be higher depending on z-index
            
            canvas.renderAll();
            isHistoryAction = false;
            updateHistoryButtons();
        });
    }

    $('#btnUndo').on('click', function() {
        if (historyIndex > 0) {
            historyIndex--;
            loadHistory(historyIndex);
        }
    });

    $('#btnRedo').on('click', function() {
        if (historyIndex < history.length - 1) {
            historyIndex++;
            loadHistory(historyIndex);
        }
    });

    $('#btnReset').on('click', function() {
        if (confirm('Clear everything and start over?')) {
            window.location.reload();
        }
    });

    canvas.on('object:modified', saveHistory);

    // ─── 7. CATEGORY & FAVORITES FILTERING ─────────────────────────

    $('.category-filter').on('click', function(e) {
        e.preventDefault();
        $('.category-filter').removeClass('active');
        $(this).addClass('active');
        
        const cat = $(this).data('cat');
        $('#currentCategoryLabel').text($(this).text().trim());

        $('.frame-thumbnail').each(function() {
            if (cat === 'all') {
                $(this).show();
            } else if (cat === 'favorites') {
                $(this).toggle($(this).find('.frame-fav-btn').hasClass('favorited'));
            } else {
                $(this).toggle($(this).data('cat') == cat);
            }
        });
    });

    $('#frameSearch').on('input', function() {
        const val = $(this).val().toLowerCase();
        $('.frame-thumbnail').each(function() {
            const name = $(this).data('name');
            $(this).toggle(name.includes(val));
        });
    });

    // Favorites using localStorage
    let favs = JSON.parse(localStorage.getItem('ifg_favs') || '[]');
    
    // Apply init fav state
    $('.frame-fav-btn').each(function() {
        const id = $(this).data('id');
        if (favs.includes(id)) {
            $(this).addClass('favorited').removeClass('fa-regular').addClass('fa-solid');
        } else {
            $(this).removeClass('fa-solid').addClass('fa-regular');
        }
    });

    $('.frame-fav-btn').on('click', function(e) {
        e.stopPropagation();
        const id = $(this).data('id');
        const idx = favs.indexOf(id);
        
        if (idx > -1) {
            favs.splice(idx, 1);
            $(this).removeClass('favorited fa-solid').addClass('fa-regular');
        } else {
            favs.push(id);
            $(this).addClass('favorited fa-solid').removeClass('fa-regular');
        }
        localStorage.setItem('ifg_favs', JSON.stringify(favs));
    });

    // ─── 8. EXPORT & GENERATION ────────────────────────────────────

    $('.btn-download, .btn-download-primary').on('click', function() {
        if (!currentConfig) return alert('Please select a frame overlay.');
        if (currentConfig.image && !userImgObj && !uploadedUserPath) return alert('Please upload a photo.');

        const format = $(this).data('format'); // png | jpg
        const quality = $(this).data('quality'); // sd | hd

        if (quality === 'sd') {
            // Client-side quick download
            canvas.discardActiveObject();
            canvas.renderAll();
            
            const dataUrl = canvas.toDataURL({
                format: format === 'jpg' ? 'jpeg' : 'png',
                quality: format === 'jpg' ? 0.85 : 1,
                multiplier: 0.75 // Scale down slightly for SD
            });
            
            triggerDownload(dataUrl, 'frame_sd_' + Date.now() + '.' + format, format);
        } else {
            // Server-side HD Generation via GD
            if (isHDGenerating) return;
            isHDGenerating = true;
            $('#loadingOverlay').removeClass('d-none');

            // Collect exact coordinates from Fabric objects
            const payload = {
                csrf_token: csrfToken,
                frame_id: currentConfig.db_id,
                user_image_path: uploadedUserPath,
                logo_path: uploadedLogoPath,
                
                // Photo
                image_x: userImgObj ? userImgObj.left : 0,
                image_y: userImgObj ? userImgObj.top : 0,
                image_scale_x: userImgObj ? userImgObj.scaleX : 1,
                image_scale_y: userImgObj ? userImgObj.scaleY : 1,
                image_angle: userImgObj ? userImgObj.angle : 0,
                
                // Logo
                logo_x: logoImgObj ? logoImgObj.left : 0,
                logo_y: logoImgObj ? logoImgObj.top : 0,
                logo_scale_x: logoImgObj ? logoImgObj.scaleX : 1,
                logo_scale_y: logoImgObj ? logoImgObj.scaleY : 1,
                
                // Typography
                headline: headlineObj ? headlineObj.text : '',
                headline_x: headlineObj ? headlineObj.left : 0,
                headline_y: headlineObj ? headlineObj.top : 0,
                headline_size: headlineObj ? headlineObj.fontSize : 42,
                headline_color: headlineObj ? headlineObj.fill : '#ffffff',
                headline_font: $('#fontFamilySelect').val(),
                headline_bold: $('#btnBold').is(':checked') ? 1 : 0,
                headline_shadow: $('#btnShadow').is(':checked') ? 1 : 0,
                headline_align: $('.align-opt.active').data('align') || 'center',
                
                subheading: subheadingObj ? subheadingObj.text : '',
                reporter: reporterObj ? reporterObj.text : '',
                
                // Formats
                format: format,
                quality: 'hd'
            };

            $.ajax({
                url: baseUrl + '/generate.php',
                type: 'POST',
                data: payload,
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        // Success, download the returned image file
                        window.location.href = baseUrl + '/download.php?file=' + encodeURIComponent(res.filename);
                    } else {
                        alert('Generation Failed: ' + res.message);
                    }
                },
                error: function() {
                    alert('Server error during image generation.');
                },
                complete: function() {
                    $('#loadingOverlay').addClass('d-none');
                    isHDGenerating = false;
                }
            });
        }
    });

    function triggerDownload(base64, filename, format) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = baseUrl + '/download.php';
        form.style.display = 'none';

        const i1 = document.createElement('input'); i1.name = 'base64_image'; i1.value = base64;
        const i2 = document.createElement('input'); i2.name = 'filename'; i2.value = filename;
        const i3 = document.createElement('input'); i3.name = 'mime'; i3.value = format === 'jpg' ? 'image/jpeg' : 'image/png';
        const i4 = document.createElement('input'); i4.name = 'csrf_token'; i4.value = csrfToken;

        form.appendChild(i1); form.appendChild(i2); form.appendChild(i3); form.appendChild(i4);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    // Initialize first frame if available
    const first = $('.frame-thumbnail').first();
    if (first.length) first.trigger('click');

});
