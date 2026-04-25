<div class="upload-widget">
    <h3><?php echo htmlspecialchars($title ?? 'Upload Document'); ?></h3>
    <div class="drop-zone" id="dropZone_<?php echo $id ?? 'main'; ?>">
        <span class="drop-zone__prompt">Drag & Drop file here or click to upload</span>
        <input type="file" name="file" class="drop-zone__input" data-type="<?php echo $doc_type ?? 'General'; ?>" hidden>
    </div>
    <div class="progress-container" style="display:none; margin-top: 10px;">
        <div class="progress-bar" style="width: 0%; height: 5px; background: var(--primary-color); transition: width 0.3s;"></div>
        <span class="progress-text" style="font-size: 0.8em; color: var(--text-muted);">0%</span>
    </div>
    <div class="upload-message" style="margin-top: 10px; font-size: 0.9em;"></div>
</div>

<style>
.drop-zone {
    width: 100%;
    height: 150px;
    padding: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    font-weight: 500;
    font-size: 1.1em;
    cursor: pointer;
    color: var(--text-muted);
    border: 2px dashed var(--border-color);
    border-radius: 10px;
    transition: all 0.2s;
    background: var(--bg-card);
}

.drop-zone--over {
    border-style: solid;
    border-color: var(--primary-color);
    background: rgba(var(--primary-rgb), 0.05); /* Needs RGB var or approx */
    background: #eef2ff;
}

.drop-zone__thumb {
    width: 100%;
    height: 100%;
    border-radius: 10px;
    overflow: hidden;
    background-color: #cccccc;
    background-size: cover;
    position: relative;
}

.drop-zone__thumb::after {
    content: attr(data-label);
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    padding: 5px 0;
    color: white;
    background: rgba(0,0,0,0.75);
    font-size: 14px;
    text-align: center;
}
</style>

<script>
document.querySelectorAll(".drop-zone__input").forEach((inputElement) => {
    const dropZoneElement = inputElement.closest(".drop-zone");

    dropZoneElement.addEventListener("click", (e) => {
        inputElement.click();
    });

    inputElement.addEventListener("change", (e) => {
        if (inputElement.files.length) {
            handleFileUpload(inputElement.files[0], dropZoneElement);
        }
    });

    dropZoneElement.addEventListener("dragover", (e) => {
        e.preventDefault();
        dropZoneElement.classList.add("drop-zone--over");
    });

    ["dragleave", "dragend"].forEach((type) => {
        dropZoneElement.addEventListener(type, (e) => {
            dropZoneElement.classList.remove("drop-zone--over");
        });
    });

    dropZoneElement.addEventListener("drop", (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            inputElement.files = e.dataTransfer.files;
            handleFileUpload(e.dataTransfer.files[0], dropZoneElement);
        }
        dropZoneElement.classList.remove("drop-zone--over");
    });
});

function handleFileUpload(file, dropZone) {
    const widget = dropZone.closest('.upload-widget');
    const progressBar = widget.querySelector('.progress-bar');
    const progressContainer = widget.querySelector('.progress-container');
    const progressText = widget.querySelector('.progress-text');
    const msgDiv = widget.querySelector('.upload-message');
    const input = dropZone.querySelector('input');
    
    progressContainer.style.display = 'block';
    msgDiv.innerText = 'Uploading ' + file.name + '...';
    msgDiv.style.color = 'var(--text-muted)';
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('doc_type', input.dataset.type);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'upload_handler.php', true);

    xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
            const percent = (e.loaded / e.total) * 100;
            progressBar.style.width = percent + '%';
            progressText.innerText = Math.round(percent) + '%';
        }
    };

    xhr.onload = () => {
        if (xhr.status === 200) {
            try {
                const resp = JSON.parse(xhr.responseText);
                if (resp.success) {
                    msgDiv.innerText = resp.message;
                    msgDiv.style.color = 'var(--success-color)';
                    // Optional: Update thumb or checklist
                } else {
                    msgDiv.innerText = 'Error: ' + resp.error;
                    msgDiv.style.color = 'var(--danger-color)';
                }
            } catch (e) {
                msgDiv.innerText = 'Upload failed: Invalid response';
                msgDiv.style.color = 'var(--danger-color)';
            }
        } else {
             msgDiv.innerText = 'Server Error';
             msgDiv.style.color = 'var(--danger-color)';
        }
    };

    xhr.send(formData);
}
</script>
