
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>

    <style>
    #ratio {
        width: 70px;
        background-color: #eee !important;
        color: #666;
        cursor: not-allowed;
    }
    .tshirt-canvas-wrapper {
        width: 100%;
        max-width: 1024px;
        margin: 0 auto;
    }
    canvas#tshirt-canvas {
        width: 100% !important;
        height: auto !important;
        display: block;
        border: 1px solid #ccc;
    }
    .tshirt-controls {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }
    .tshirt-controls-inner {
        background: rgba(255, 255, 255, 0.8);
        padding: 10px;
        border-radius: 10px;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }
    .tshirt-controls-inner select,
    .tshirt-controls-inner button,
    .tshirt-controls-inner label,
    .tshirt-controls-inner input[type="number"],
    .tshirt-controls-inner input[type="text"] {
        height: 36px;
        font-size: 14px;
        padding: 0 12px;
        border: 1px solid #ccc;
        background-color: white;
        color: #333;
        border-radius: 4px;
        cursor: pointer;
    }
    .tshirt-controls-inner label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .tshirt-controls-inner input[type="file"] {
        display: none;
    }
    .tshirt-controls-inner span {
        display: inline-flex;
        align-items: center;
        height: 36px;
        font-size: 14px;
        padding: 0 10px;
    }
    </style>

    <div class="tshirt-controls">
        <div class="tshirt-controls-inner">
            <span>Select the Blank T-Shirt：</span>
            <select id="bgSelect">
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/B_T.jpg" selected>Black Front Side</option>
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/B_T_back.jpg">Black Back Side</option>
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/H_T.jpg">Heather Front Side</option>
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/H_T_Back.jpg">Heather Back Side</option>
            </select>

            <span>Select Size：</span>
            <select id="sizeSelect">
                <option value="0.1">S</option>
                <option value="0.104" selected>M</option>
                <option value="0.108">L</option>
            </select>

            <span>ratio：</span>
            <input type="text" id="ratio" value="0.104" readonly>

            <input type="file" id="imgUploader" accept="image/*">
            <label for="imgUploader">Upload</label>
            <button id="downloadBtn">Download</button>
			<button id="centerBtn">Center</button>
            <button id="resetBtn">Clear</button>
            <label>
                <input type="checkbox" id="showSizeLabels" checked>
                Show W & H (cm)
            </label>
        </div>
    </div>

    <div class="tshirt-canvas-wrapper">
        <canvas id="tshirt-canvas" width="2048" height="2048"></canvas>
    </div>

    <script>
    let CM_PER_PIXEL = parseFloat(document.getElementById('sizeSelect').value);
    document.getElementById('ratio').value = CM_PER_PIXEL;

    const canvas = new fabric.Canvas('tshirt-canvas', {
        enableRetinaScaling: true,
        devicePixelRatio: window.devicePixelRatio || 1
    });
    let currentBgUrl = document.getElementById('bgSelect').value;

    function setBackground(url) {
        fabric.Image.fromURL(url, function(img) {
            img.selectable = false;
            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                scaleX: canvas.width / img.width,
                scaleY: canvas.height / img.height,
                crossOrigin: 'anonymous'
            });
        }, { crossOrigin: 'anonymous' });
    }

    function resizeCanvasIfMobile() {
        const wrapper = document.querySelector('.tshirt-canvas-wrapper');
        const screenWidth = window.innerWidth;
        const devicePixelRatio = window.devicePixelRatio || 1;

        if (screenWidth < 768) {
            const width = wrapper.clientWidth;
            canvas.setWidth(width);
            canvas.setHeight(width);
            canvas.setZoom(1 / devicePixelRatio);
            canvas.calcOffset();
            setBackground(currentBgUrl);
        }
    }

    function updateAllSizeLabels() {
        canvas.getObjects('text').forEach(t => canvas.remove(t));
        canvas.getObjects().forEach(obj => {
            if (obj.type === 'image') addSizeLabel(obj);
        });
    }

    function addSizeLabel(obj) {
        if (window.innerWidth < 768) return;
        const showLabels = document.getElementById('showSizeLabels');
        if (!showLabels || !showLabels.checked) return;

        canvas.getObjects('text').forEach(t => {
            if (t.customLabelFor === obj) canvas.remove(t);
        });

        const scaledWidth = obj.getScaledWidth();
        const scaledHeight = obj.getScaledHeight();
        const realWidthCM = (scaledWidth * CM_PER_PIXEL).toFixed(2);
        const realHeightCM = (scaledHeight * CM_PER_PIXEL).toFixed(2);
        const bounds = obj.getBoundingRect(true);
        const labelText = `W: ${realWidthCM} cm  H: ${realHeightCM} cm`;

        const label = new fabric.Text(labelText, {
            left: bounds.left + bounds.width / 2,
            top: bounds.top - 30,
            originX: 'center',
            originY: 'bottom',
            fontSize: 16,
            fill: 'red',
            selectable: false,
            evented: false
        });

        label.customLabelFor = obj;
        canvas.add(label);
        canvas.renderAll();
    }

		document.getElementById('centerBtn').addEventListener('click', function () {
		const activeObj = canvas.getActiveObject();
		if (activeObj && activeObj.type === 'image') {
			activeObj.set({
				left: (canvas.getWidth() - activeObj.getScaledWidth()) / 2,
			});
			canvas.renderAll();
			addSizeLabel(activeObj);
		} else {
			alert('請先選取一個圖案');
		}
	});
		
    document.getElementById('bgSelect').addEventListener('change', function() {
        currentBgUrl = this.value;
        setBackground(currentBgUrl);
    });
		
	// 監聽 WebGL context lost / restored
	canvas.getElement().addEventListener('webglcontextlost', function(event) {
		event.preventDefault();
		alert('圖像渲染暫停，正在嘗試恢復...');
	});

	canvas.getElement().addEventListener('webglcontextrestored', function() {
		alert('圖像渲染已恢復，正在重載背景圖...');
		setBackground(currentBgUrl);
		canvas.renderAll();
	});
		

    document.getElementById('showSizeLabels').addEventListener('change', updateAllSizeLabels);

    document.getElementById('sizeSelect').addEventListener('change', function () {
        CM_PER_PIXEL = parseFloat(this.value);
        document.getElementById('ratio').value = CM_PER_PIXEL;
        updateAllSizeLabels();
    });

    document.getElementById('imgUploader').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(f) {
            const tempImg = new Image();
            tempImg.crossOrigin = 'anonymous';
            tempImg.onload = function() {
                const tempCanvas = document.createElement('canvas');
                const ctx = tempCanvas.getContext('2d');
                tempCanvas.width = tempImg.width;
                tempCanvas.height = tempImg.height;
                ctx.drawImage(tempImg, 0, 0);

                const imageData = ctx.getImageData(0, 0, tempCanvas.width, tempCanvas.height);
                const data = imageData.data;

                let minX = tempCanvas.width, minY = tempCanvas.height, maxX = 0, maxY = 0;
                for (let y = 0; y < tempCanvas.height; y++) {
                    for (let x = 0; x < tempCanvas.width; x++) {
                        const idx = (y * tempCanvas.width + x) * 4 + 3;
                        if (data[idx] > 0) {
                            if (x < minX) minX = x;
                            if (x > maxX) maxX = x;
                            if (y < minY) minY = y;
                            if (y > maxY) maxY = y;
                        }
                    }
                }

                if (maxX < minX || maxY < minY) {
                    alert('圖片全透明或無法裁剪');
                    return;
                }

                const cropWidth = maxX - minX + 1;
                const cropHeight = maxY - minY + 1;

                const cropCanvas = document.createElement('canvas');
                const cropCtx = cropCanvas.getContext('2d');
                cropCanvas.width = cropWidth;
                cropCanvas.height = cropHeight;
                cropCtx.drawImage(tempImg, minX, minY, cropWidth, cropHeight, 0, 0, cropWidth, cropHeight);

                const croppedDataUrl = cropCanvas.toDataURL();

                fabric.Image.fromURL(croppedDataUrl, function(img) {
                    const maxWidth = canvas.getWidth() * 0.3;
                    if (img.width > maxWidth) img.scaleToWidth(maxWidth);

                    img.set({
                        left: (canvas.getWidth() - img.getScaledWidth()) / 2,
                        top: (canvas.getHeight() - img.getScaledHeight()) / 2
                    });

                    img.lockUniScaling = true;

                    img.setControlsVisibility({
                        mt: false, mb: false, ml: false, mr: false
                    });

                    canvas.add(img).setActiveObject(img);
                    addSizeLabel(img);

                    img.on('modified', function() { addSizeLabel(img); });
                    img.on('moving', function() { addSizeLabel(img); });
                    img.on('scaling', function() { addSizeLabel(img); });

                }, { crossOrigin: 'anonymous' });
            };
            tempImg.src = f.target.result;
        };
        reader.readAsDataURL(file);
        e.target.value = '';
    });

    document.getElementById('downloadBtn').addEventListener('click', function() {
        canvas.renderAll();
        try {
            const tempCanvas = document.createElement('canvas');
            const tempCtx = tempCanvas.getContext('2d');
            const devicePixelRatio = window.devicePixelRatio || 1;
            
            tempCanvas.width = canvas.width * devicePixelRatio;
            tempCanvas.height = canvas.height * devicePixelRatio;
            
            tempCtx.scale(devicePixelRatio, devicePixelRatio);
            
            tempCtx.drawImage(canvas.lowerCanvasEl, 0, 0);
            
            const dataURL = tempCanvas.toDataURL({ 
                format: 'png', 
                quality: 1.0,
                multiplier: devicePixelRatio
            });
            
            const link = document.createElement('a');
            link.href = dataURL;
            link.download = 'tshirt_design.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        } catch (error) {
            alert('下載失敗，可能是圖片來源有 CORS 限制');
            console.error(error);
        }
    });

    document.getElementById('resetBtn').addEventListener('click', function() {
        const objects = canvas.getObjects().slice();
        objects.forEach(obj => {
            if (obj !== canvas.backgroundImage) canvas.remove(obj);
        });
        canvas.discardActiveObject().renderAll();
    });

    canvas.on('object:removed', function(e) {
        const removedObj = e.target;
        if (!removedObj) return;

        canvas.getObjects('text').forEach(t => {
            if (t.customLabelFor === removedObj) {
                canvas.remove(t);
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Delete' || e.key === 'Backspace') {
            const activeObj = canvas.getActiveObject();
            if (activeObj && activeObj !== canvas.backgroundImage) {
                canvas.remove(activeObj);
            }
        }
    });

    window.addEventListener('load', resizeCanvasIfMobile);
    setBackground(currentBgUrl);
    </script>
    <?php
    return ob_get_clean();
