/**
 * interactive-canvas.js
 * Xử lý tương tác Chạm điểm (Hotspot Pinpoint) và Khoanh vùng (Polygon Defect) trên màn hình cảm ứng & chuột
 */

class InteractiveCanvas {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        this.type = options.type || 'HOTSPOT_PINPOINT'; // 'HOTSPOT_PINPOINT' | 'POLYGON_DEFECT'
        this.imageUrl = options.imageUrl || '';
        this.onUpdate = options.onUpdate || function() {};
        this.readOnly = options.readOnly || false;

        this.image = new Image();
        this.isImageLoaded = false;

        // Dữ liệu câu trả lời (tính theo %)
        this.pinpoint = options.initialPinpoint || null; // { xPct, yPct }
        this.polygonPoints = options.initialPolygon || []; // [ { x, y }, ... ]
        this.isDrawing = false;

        this.init();
    }

    init() {
        this.image.crossOrigin = 'anonymous';
        this.image.src = this.imageUrl;
        this.image.onload = () => {
            this.isImageLoaded = true;
            this.resizeCanvas();
            this.render();
        };

        window.addEventListener('resize', () => {
            this.resizeCanvas();
            this.render();
        });

        if (!this.readOnly) {
            this.bindEvents();
        }
    }

    resizeCanvas() {
        if (!this.isImageLoaded) return;
        const container = this.canvas.parentElement;
        const containerWidth = container.clientWidth;
        // Giữ đúng tỷ lệ ảnh gốc (aspect ratio)
        const aspectRatio = this.image.naturalHeight / this.image.naturalWidth;
        this.canvas.width = containerWidth;
        this.canvas.height = containerWidth * aspectRatio;
    }

    bindEvents() {
        // Hỗ trợ cả Touch (Mobile / Tablet) và Mouse (Desktop)
        const getCanvasCoords = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            let clientX, clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else if (e.changedTouches && e.changedTouches.length > 0) {
                clientX = e.changedTouches[0].clientX;
                clientY = e.changedTouches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }
            return {
                x: clientX - rect.left,
                y: clientY - rect.top,
                xPct: parseFloat(((clientX - rect.left) / this.canvas.width * 100).toFixed(2)),
                yPct: parseFloat(((clientY - rect.top) / this.canvas.height * 100).toFixed(2))
            };
        };

        if (this.type === 'HOTSPOT_PINPOINT') {
            const handleTap = (e) => {
                e.preventDefault();
                const coords = getCanvasCoords(e);
                this.pinpoint = { xPct: coords.xPct, yPct: coords.yPct };
                this.render();
                this.onUpdate({
                    clicked_x_pct: coords.xPct,
                    clicked_y_pct: coords.yPct
                });
            };

            this.canvas.addEventListener('click', handleTap);
            this.canvas.addEventListener('touchstart', handleTap, { passive: false });
        } else if (this.type === 'POLYGON_DEFECT') {
            // Khoanh vùng bằng cách kéo ngón tay hoặc bấm nhiều điểm
            const startDraw = (e) => {
                e.preventDefault();
                this.isDrawing = true;
                const coords = getCanvasCoords(e);
                this.polygonPoints = [{ x: coords.xPct, y: coords.yPct }];
                this.render();
            };

            const moveDraw = (e) => {
                if (!this.isDrawing) return;
                e.preventDefault();
                const coords = getCanvasCoords(e);
                // Giới hạn tần suất lưu điểm để tránh quá tải
                const last = this.polygonPoints[this.polygonPoints.length - 1];
                if (!last || Math.hypot(coords.xPct - last.x, coords.yPct - last.y) > 1.5) {
                    this.polygonPoints.push({ x: coords.xPct, y: coords.yPct });
                    this.render();
                }
            };

            const endDraw = (e) => {
                if (!this.isDrawing) return;
                this.isDrawing = false;
                if (this.polygonPoints.length > 2) {
                    this.render();
                    this.onUpdate({
                        drawn_polygon: this.polygonPoints
                    });
                }
            };

            this.canvas.addEventListener('mousedown', startDraw);
            window.addEventListener('mousemove', moveDraw);
            window.addEventListener('mouseup', endDraw);

            this.canvas.addEventListener('touchstart', startDraw, { passive: false });
            this.canvas.addEventListener('touchmove', moveDraw, { passive: false });
            this.canvas.addEventListener('touchend', endDraw, { passive: false });
        }
    }

    clear() {
        this.pinpoint = null;
        this.polygonPoints = [];
        this.render();
        this.onUpdate(null);
    }

    render() {
        if (!this.isImageLoaded) return;
        const w = this.canvas.width;
        const h = this.canvas.height;
        this.ctx.clearRect(0, 0, w, h);

        // 1. Vẽ ảnh nền linh kiện / bảng điều khiển
        this.ctx.drawImage(this.image, 0, 0, w, h);

        // 2. Vẽ điểm Pinpoint nếu có
        if (this.pinpoint) {
            const px = (this.pinpoint.xPct / 100) * w;
            const py = (this.pinpoint.yPct / 100) * h;

            // Vòng tròn định vị ngoài
            this.ctx.beginPath();
            this.ctx.arc(px, py, 24, 0, Math.PI * 2);
            this.ctx.strokeStyle = '#ef4444';
            this.ctx.lineWidth = 3;
            this.ctx.setLineDash([4, 4]);
            this.ctx.stroke();
            this.ctx.setLineDash([]);

            // Tâm định vị
            this.ctx.beginPath();
            this.ctx.arc(px, py, 6, 0, Math.PI * 2);
            this.ctx.fillStyle = '#dc2626';
            this.ctx.fill();

            // Chữ thập tâm
            this.ctx.beginPath();
            this.ctx.moveTo(px - 16, py);
            this.ctx.lineTo(px + 16, py);
            this.ctx.moveTo(px, py - 16);
            this.ctx.lineTo(px, py + 16);
            this.ctx.strokeStyle = '#ffffff';
            this.ctx.lineWidth = 2;
            this.ctx.stroke();

            // Label tọa độ
            this.ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
            this.ctx.fillRect(px - 45, py - 38, 90, 20);
            this.ctx.fillStyle = '#38bdf8';
            this.ctx.font = 'bold 10px monospace';
            this.ctx.textAlign = 'center';
            this.ctx.fillText(`${this.pinpoint.xPct}%, ${this.pinpoint.yPct}%`, px, py - 24);
        }

        // 3. Vẽ vùng Polygon Defect nếu có
        if (this.polygonPoints && this.polygonPoints.length > 1) {
            this.ctx.beginPath();
            const firstX = (this.polygonPoints[0].x / 100) * w;
            const firstY = (this.polygonPoints[0].y / 100) * h;
            this.ctx.moveTo(firstX, firstY);

            for (let i = 1; i < this.polygonPoints.length; i++) {
                const px = (this.polygonPoints[i].x / 100) * w;
                const py = (this.polygonPoints[i].y / 100) * h;
                this.ctx.lineTo(px, py);
            }

            if (!this.isDrawing) {
                this.ctx.closePath();
            }

            this.ctx.fillStyle = 'rgba(239, 68, 68, 0.35)'; // Màu đỏ cảnh báo lỗi trong suốt
            this.ctx.fill();

            this.ctx.strokeStyle = '#ef4444';
            this.ctx.lineWidth = 3;
            this.ctx.stroke();
        }
    }
}

