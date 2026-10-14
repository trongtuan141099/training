/**
 * assets/js/interactive-canvas.js
 * Thư viện Canvas Tương tác & Trình Biên tập Vùng Lỗi (Hotspot & ROI Editor)
 * Dành cho Thiết bị Nhà máy (Mobile touch & Desktop mouse)
 */

// =========================================================================
// 1. CLASS InteractiveCanvas: Dành cho Công nhân làm bài thi (Chạm điểm / Khoanh vùng)
// =========================================================================
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
        const containerWidth = container.clientWidth || 600;
        const aspectRatio = (this.image.naturalHeight || 300) / (this.image.naturalWidth || 600);
        this.canvas.width = containerWidth;
        this.canvas.height = Math.max(180, containerWidth * aspectRatio);
    }

    bindEvents() {
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
                const last = this.polygonPoints[this.polygonPoints.length - 1];
                if (!last || Math.hypot(coords.xPct - last.x, coords.yPct - last.y) > 1.2) {
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
                        drawn_polygon: this.polygonPoints,
                        clicked_x_pct: this.polygonPoints[0].x,
                        clicked_y_pct: this.polygonPoints[0].y
                    });
                } else if (this.polygonPoints.length > 0) {
                    // Nếu công nhân chỉ click 1 điểm thay vì vẽ đường tròn
                    const pt = this.polygonPoints[0];
                    this.onUpdate({
                        clicked_x_pct: pt.x,
                        clicked_y_pct: pt.y,
                        drawn_polygon: [pt]
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

        // 1. Vẽ ảnh nền
        this.ctx.drawImage(this.image, 0, 0, w, h);

        // 2. Vẽ điểm Pinpoint nếu có
        if (this.pinpoint) {
            const px = (this.pinpoint.xPct / 100) * w;
            const py = (this.pinpoint.yPct / 100) * h;

            this.ctx.beginPath();
            this.ctx.arc(px, py, 22, 0, Math.PI * 2);
            this.ctx.strokeStyle = '#ef4444';
            this.ctx.lineWidth = 3;
            this.ctx.setLineDash([4, 4]);
            this.ctx.stroke();
            this.ctx.setLineDash([]);

            this.ctx.beginPath();
            this.ctx.arc(px, py, 6, 0, Math.PI * 2);
            this.ctx.fillStyle = '#dc2626';
            this.ctx.fill();

            this.ctx.beginPath();
            this.ctx.moveTo(px - 14, py);
            this.ctx.lineTo(px + 14, py);
            this.ctx.moveTo(px, py - 14);
            this.ctx.lineTo(px, py + 14);
            this.ctx.strokeStyle = '#ffffff';
            this.ctx.lineWidth = 2;
            this.ctx.stroke();

            this.ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
            this.ctx.fillRect(px - 45, py - 36, 90, 18);
            this.ctx.fillStyle = '#38bdf8';
            this.ctx.font = 'bold 10px monospace';
            this.ctx.textAlign = 'center';
            this.ctx.fillText(`${this.pinpoint.xPct}%, ${this.pinpoint.yPct}%`, px, py - 23);
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

            this.ctx.fillStyle = 'rgba(239, 68, 68, 0.35)';
            this.ctx.fill();

            this.ctx.strokeStyle = '#ef4444';
            this.ctx.lineWidth = 3;
            this.ctx.stroke();
        }
    }
}

// =========================================================================
// 2. CLASS RoiEditor: Trình Biên tập Khoanh vùng lỗi & Hotspot cho Giảng viên (Trainer)
// =========================================================================
class RoiEditor {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        this.ctx = this.canvas.getContext('2d');
        this.imageUrl = options.imageUrl || '';
        this.regions = options.initialRegions || []; // [ { id, type: 'rect'|'hotspot', label, x, y, w, h, r } ]
        this.mode = options.mode || 'rect'; // 'rect' (Bounding Box) | 'hotspot' (Chạm điểm)
        this.activeLabel = options.label || 'Vùng lỗi #1';
        this.toleranceRadius = options.tolerance || 7.5;
        this.onUpdate = options.onUpdate || function() {};

        this.isDrawing = false;
        this.startPos = { x: 0, y: 0, xPct: 0, yPct: 0 };
        this.currentPos = { x: 0, y: 0, xPct: 0, yPct: 0 };

        this.image = new Image();
        this.isImageLoaded = false;
        this.init();
    }

    init() {
        this.loadImage(this.imageUrl);

        window.addEventListener('resize', () => {
            this.resizeCanvas();
            this.render();
        });

        this.bindEvents();
    }

    loadImage(url) {
        if (!url) return;
        this.imageUrl = url;
        this.isImageLoaded = false;
        this.image.crossOrigin = 'anonymous';
        this.image.src = url;
        this.image.onload = () => {
            this.isImageLoaded = true;
            this.resizeCanvas();
            this.render();
        };
    }

    setMode(mode) {
        this.mode = mode;
        this.render();
    }

    setLabel(label) {
        this.activeLabel = label || 'Vùng lỗi #1';
    }

    setTolerance(tol) {
        this.toleranceRadius = parseFloat(tol) || 7.5;
        this.render();
    }

    resizeCanvas() {
        if (!this.isImageLoaded) return;
        const container = this.canvas.parentElement;
        const containerWidth = container.clientWidth || 600;
        const aspectRatio = (this.image.naturalHeight || 300) / (this.image.naturalWidth || 600);
        this.canvas.width = containerWidth;
        this.canvas.height = Math.max(220, containerWidth * aspectRatio);
    }

    bindEvents() {
        const getCoords = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            let clientX, clientY;
            if (e.touches && e.touches.length > 0) {
                clientX = e.touches[0].clientX;
                clientY = e.touches[0].clientY;
            } else {
                clientX = e.clientX;
                clientY = e.clientY;
            }
            const x = clientX - rect.left;
            const y = clientY - rect.top;
            return {
                x: x,
                y: y,
                xPct: parseFloat((x / this.canvas.width * 100).toFixed(2)),
                yPct: parseFloat((y / this.canvas.height * 100).toFixed(2))
            };
        };

        const onDown = (e) => {
            if (!this.isImageLoaded) return;
            e.preventDefault();
            const coords = getCoords(e);
            this.isDrawing = true;
            this.startPos = coords;
            this.currentPos = coords;

            if (this.mode === 'hotspot') {
                // Thêm ngay 1 hotspot tại điểm click
                const newRegion = {
                    id: 'roi_' + Date.now() + '_' + Math.floor(Math.random() * 100),
                    type: 'hotspot',
                    label: this.activeLabel,
                    x: coords.xPct,
                    y: coords.yPct,
                    r: this.toleranceRadius
                };
                this.regions.push(newRegion);
                this.isDrawing = false;
                this.render();
                this.notifyUpdate();
            }
        };

        const onMove = (e) => {
            if (!this.isDrawing || this.mode !== 'rect') return;
            e.preventDefault();
            this.currentPos = getCoords(e);
            this.render();
        };

        const onUp = (e) => {
            if (!this.isDrawing || this.mode !== 'rect') return;
            e.preventDefault();
            this.isDrawing = false;
            const coords = getCoords(e);

            const minX = Math.min(this.startPos.xPct, coords.xPct);
            const minY = Math.min(this.startPos.yPct, coords.yPct);
            const wPct = Math.abs(coords.xPct - this.startPos.xPct);
            const hPct = Math.abs(coords.yPct - this.startPos.yPct);

            // Bỏ qua nếu vùng vẽ quá nhỏ (< 2%)
            if (wPct >= 2.0 && hPct >= 2.0) {
                const newRegion = {
                    id: 'roi_' + Date.now() + '_' + Math.floor(Math.random() * 100),
                    type: 'rect',
                    label: this.activeLabel,
                    x: parseFloat(minX.toFixed(2)),
                    y: parseFloat(minY.toFixed(2)),
                    w: parseFloat(wPct.toFixed(2)),
                    h: parseFloat(hPct.toFixed(2))
                };
                this.regions.push(newRegion);
                this.notifyUpdate();
            }
            this.render();
        };

        this.canvas.addEventListener('mousedown', onDown);
        window.addEventListener('mousemove', onMove);
        window.addEventListener('mouseup', onUp);

        this.canvas.addEventListener('touchstart', onDown, { passive: false });
        this.canvas.addEventListener('touchmove', onMove, { passive: false });
        this.canvas.addEventListener('touchend', onUp, { passive: false });
    }

    removeRegion(id) {
        this.regions = this.regions.filter(r => r.id !== id);
        this.render();
        this.notifyUpdate();
    }

    clearAll() {
        this.regions = [];
        this.render();
        this.notifyUpdate();
    }

    notifyUpdate() {
        this.onUpdate(this.regions);
    }

    render() {
        if (!this.isImageLoaded) return;
        const w = this.canvas.width;
        const h = this.canvas.height;
        this.ctx.clearRect(0, 0, w, h);

        // 1. Vẽ ảnh nền
        this.ctx.drawImage(this.image, 0, 0, w, h);

        // 2. Vẽ tất cả các vùng ROI đã thiết lập
        this.regions.forEach((roi, idx) => {
            const isHotspot = (roi.type === 'hotspot');
            const color = isHotspot ? '#06b6d4' : '#ef4444'; // Xanh ngọc cho Hotspot, Đỏ cho Bounding Box

            if (isHotspot) {
                const cx = (roi.x / 100) * w;
                const cy = (roi.y / 100) * h;
                const rad = ((roi.r || 7.5) / 100) * w;

                // Vùng bán kính dung sai
                this.ctx.beginPath();
                this.ctx.arc(cx, cy, rad, 0, Math.PI * 2);
                this.ctx.fillStyle = 'rgba(6, 182, 212, 0.25)';
                this.ctx.fill();
                this.ctx.strokeStyle = color;
                this.ctx.lineWidth = 2;
                this.ctx.setLineDash([4, 4]);
                this.ctx.stroke();
                this.ctx.setLineDash([]);

                // Tâm điểm
                this.ctx.beginPath();
                this.ctx.arc(cx, cy, 5, 0, Math.PI * 2);
                this.ctx.fillStyle = color;
                this.ctx.fill();

                // Nhãn
                this.ctx.fillStyle = 'rgba(15, 23, 42, 0.85)';
                this.ctx.fillRect(cx - 50, cy - 32, 100, 18);
                this.ctx.fillStyle = '#ffffff';
                this.ctx.font = 'bold 9px sans-serif';
                this.ctx.textAlign = 'center';
                this.ctx.fillText(`#${idx + 1}: ${roi.label}`, cx, cy - 20);

            } else {
                // Bounding box chữ nhật
                const rx = (roi.x / 100) * w;
                const ry = (roi.y / 100) * h;
                const rw = (roi.w / 100) * w;
                const rh = (roi.h / 100) * h;

                this.ctx.fillStyle = 'rgba(239, 68, 68, 0.25)';
                this.ctx.fillRect(rx, ry, rw, rh);

                this.ctx.strokeStyle = color;
                this.ctx.lineWidth = 2.5;
                this.ctx.strokeRect(rx, ry, rw, rh);

                // Nhãn badge phía trên vùng lỗi
                this.ctx.fillStyle = '#dc2626';
                const labelText = `#${idx + 1}: ${roi.label} (${roi.w}%x${roi.h}%)`;
                this.ctx.font = 'bold 10px sans-serif';
                const textMetrics = this.ctx.measureText(labelText);
                const badgeWidth = textMetrics.width + 12;
                this.ctx.fillRect(rx, Math.max(0, ry - 18), badgeWidth, 18);
                this.ctx.fillStyle = '#ffffff';
                this.ctx.textAlign = 'left';
                this.ctx.fillText(labelText, rx + 6, Math.max(13, ry - 5));
            }
        });

        // 3. Nếu đang kéo chuột vẽ vùng chữ nhật mới
        if (this.isDrawing && this.mode === 'rect') {
            const rx = Math.min(this.startPos.x, this.currentPos.x);
            const ry = Math.min(this.startPos.y, this.currentPos.y);
            const rw = Math.abs(this.currentPos.x - this.startPos.x);
            const rh = Math.abs(this.currentPos.y - this.startPos.y);

            this.ctx.fillStyle = 'rgba(59, 130, 246, 0.3)';
            this.ctx.fillRect(rx, ry, rw, rh);

            this.ctx.strokeStyle = '#3b82f6';
            this.ctx.lineWidth = 2;
            this.ctx.setLineDash([5, 5]);
            this.ctx.strokeRect(rx, ry, rw, rh);
            this.ctx.setLineDash([]);
        }
    }
}
