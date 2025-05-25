function tshirt_designer_canvas_shortcode() {   
    ob_start();
    ?>
    <!-- 載入 Fabric.js 函式庫，這是前端的圖像編輯框架 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fabric.js/5.3.0/fabric.min.js"></script>

    <style>
    /* 畫布外層容器，設定最大寬度並置中 */
    .tshirt-canvas-wrapper {
        width: 100%;
        max-width: 1024px;
        margin: 0 auto;
    }

    /* 畫布本身的樣式：自動寬高、顯示邊框 */
    canvas#tshirt-canvas {
        width: 100% !important;
        height: auto !important;
        display: block;
        border: 1px solid #ccc;
    }

    /* 控制按鈕的整體容器樣式 */
    .tshirt-controls {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 20px;
    }

    /* 控制按鈕內部區塊，背景半透明白色 + 圓角 */
    .tshirt-controls-inner {
        background: rgba(255, 255, 255, 0.8);
        padding: 10px;
        border-radius: 10px;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
    }

    /* 所有控制按鈕和選單的樣式 */
    .tshirt-controls-inner select,
    .tshirt-controls-inner button,
    .tshirt-controls-inner label {
        height: 36px;
        font-size: 14px;
        padding: 0 20px;
        border: 1px solid #ccc;
        background-color: white;
        color: #333;
        border-radius: 4px;
        cursor: pointer;
    }

    /* 上傳按鈕樣式（其實是 label） */
    .tshirt-controls-inner label {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* 隱藏真正的上傳 input */
    .tshirt-controls-inner input[type="file"] {
        display: none;
    }

    /* 控制項前面的說明文字樣式 */
    .tshirt-controls-inner span {
        display: inline-flex;
        align-items: center;
        height: 36px;
        font-size: 14px;
        padding: 0 10px;
    }
    </style>

    <!-- 控制區塊 -->
    <div class="tshirt-controls">
        <div class="tshirt-controls-inner">
            <!-- T-shirt 圖片選擇 -->
            <span>Select the Blank T-Shirt：</span>
            <select id="bgSelect">
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/B_T.jpg" selected>Black Front Side</option>
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/B_T_back.jpg">Black Back Side</option>
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/H_T.jpg">Heather Front Side</option>
                <option value="https://cdn.jsdelivr.net/gh/rayray11s/web/customization/cdn/H_T_Back.jpg">Heather Back Side</option>
            </select>

            <!-- 上傳圖檔 -->
            <input type="file" id="imgUploader" accept="image/*">
            <label for="imgUploader">Upload</label>

            <!-- 下載圖檔 -->
            <button id="downloadBtn">Download</button>

            <!-- 清除畫面 -->
            <button id="resetBtn">Clear</button>
        </div>
    </div>

    <!-- 畫布容器 -->
    <div class="tshirt-canvas-wrapper">
        <canvas id="tshirt-canvas" width="1024" height="1024"></canvas>
    </div>

    <script>
    // 建立 Fabric 畫布實例
    const canvas = new fabric.Canvas('tshirt-canvas');
    
    // 初始化背景圖 URL（來自下拉選單）
    let currentBgUrl = document.getElementById('bgSelect').value;

    // 設定背景圖函式
    function setBackground(url) {
        fabric.Image.fromURL(url, function(img) {
            img.selectable = false; // 不可拖動或點選
            canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                scaleX: canvas.width / img.width,
                scaleY: canvas.height / img.height,
                crossOrigin: 'anonymous' // 解決跨域問題
            });
        }, { crossOrigin: 'anonymous' });
    }

    // 根據裝置螢幕寬度自動調整畫布尺寸
    function resizeCanvasForMobile() {
        const wrapper = document.querySelector('.tshirt-canvas-wrapper');
        const width = wrapper.clientWidth; // 根據容器寬度設定
        canvas.setWidth(width);
        canvas.setHeight(width);
        canvas.calcOffset(); // 重新計算偏移量
        setBackground(document.getElementById('bgSelect').value); // 重設背景
    }

    // 初始化設定
    setBackground(currentBgUrl);
    resizeCanvasForMobile();

    // 切換背景圖事件
    document.getElementById('bgSelect').addEventListener('change', function() {
        currentBgUrl = this.value;
        setBackground(currentBgUrl);
    });

    // 上傳圖案到畫布
    document.getElementById('imgUploader').addEventListener('change', function(e) {
        const reader = new FileReader();
        reader.onload = function(f) {
            fabric.Image.fromURL(f.target.result, function(img) {
                img.scaleToWidth(canvas.getWidth() * 0.5); // 圖片寬度為畫布的 50%
                canvas.add(img).setActiveObject(img); // 加入畫布並設定為選中
            });
        };
        reader.readAsDataURL(e.target.files[0]);
        e.target.value = ''; // 清除 input，可再次上傳同一張圖
    });

    // 將畫布內容下載為 PNG 檔案
    document.getElementById('downloadBtn').addEventListener('click', function() {
        canvas.renderAll(); // 確保所有內容已更新
        try {
            const dataURL = canvas.toDataURL({ format: 'png', quality: 1.0 });
            const link = document.createElement('a');
            link.href = dataURL;
            link.download = 'tshirt_design.png';
            document.body.appendChild(link);
            link.click(); // 觸發下載
            document.body.removeChild(link);
        } catch (error) {
            alert('下載失敗，可能是圖片來源有 CORS 限制');
            console.error(error);
        }
    });

    // 重設畫布（移除所有使用者圖案）
    document.getElementById('resetBtn').addEventListener('click', function() {
        const objects = canvas.getObjects().slice(); // 複製物件陣列
        objects.forEach(obj => {
            if (obj !== canvas.backgroundImage) {
                canvas.remove(obj); // 移除非背景物件
            }
        });
        canvas.discardActiveObject().renderAll(); // 清除選取狀態
    });

    // 監聽鍵盤 Delete / Backspace 鍵，刪除選取物件
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Delete' || e.key === 'Backspace') {
            const activeObj = canvas.getActiveObject();
            if (activeObj && activeObj !== canvas.backgroundImage) {
                canvas.remove(activeObj);
            }
        }
    });

    // 每次瀏覽器大小變動時，自動調整畫布尺寸
    window.addEventListener('resize', resizeCanvasForMobile);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('tshirt_designer', 'tshirt_designer_canvas_shortcode');