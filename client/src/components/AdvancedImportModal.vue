<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Импорт товаров</h2>
        <button class="close-btn" @click="$emit('close')">&times;</button>
      </div>

      <!-- Шаг 1: Загрузка файла -->
      <div v-if="step === 1" class="step">
        <h3>Шаг 1: Загрузите файл</h3>
        
        <div class="upload-area" 
          @dragover.prevent="dragover = true" 
          @dragleave.prevent="dragover = false"
          @drop.prevent="handleDrop"
          @click="$refs.fileInput.click()"
          :class="{ 'dragover': dragover }"
        >
          <input 
            type="file" 
            ref="fileInput"
            accept=".xlsx,.xls,.csv"
            @change="handleFileSelect"
            hidden
          >
          <div class="upload-content">
            <span class="icon">📁</span>
            <p>Перетащите файл сюда или кликните для выбора</p>
            <p class="hint">Поддерживаются .xlsx, .xls, .csv (до 10MB)</p>
          </div>
        </div>

        <div v-if="selectedFile" class="selected-file">
          <span>Выбран: {{ selectedFile.name }}</span>
          <span class="file-size">({{ formatFileSize(selectedFile.size) }})</span>
        </div>

        <div class="actions">
          <button @click="$emit('close')" class="btn-secondary">Отмена</button>
          <button @click="previewFile" class="btn-primary" :disabled="!selectedFile || loading">
            {{ loading ? 'Загрузка...' : 'Продолжить' }}
          </button>
        </div>
      </div>

      <!-- Шаг 2: Настройка маппинга колонок -->
      <div v-if="step === 2" class="step">
        <h3>Шаг 2: Настройте соответствие колонок</h3>
        
        <div v-if="loading" class="loading">Загрузка данных файла...</div>
        
        <div v-else class="mapping-container">
          <div class="preview-table">
            <table>
              <thead>
                <tr>
                  <th>Поле товара</th>
                  <th>Колонка в файле</th>
                  <th>Пример данных</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(field, index) in mappingFields" :key="field.key">
                  <td>
                    {{ field.label }}
                    <span v-if="field.required" class="required">*</span>
                  </td>
                  <td>
                    <select v-model="mapping[field.key]">
                      <option :value="null">— не импортировать —</option>
                      <option 
                        v-for="(header, idx) in headers" 
                        :key="idx" 
                        :value="idx"
                      >
                        {{ header }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <div v-if="mapping[field.key] !== null" class="sample">
                      {{ getSampleValue(mapping[field.key]) }}
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="detected-info" v-if="detectedMapping">
            <p>✨ Автоматически определено: 
              <span v-for="(col, field) in detectedMapping" :key="field">
                {{ field }} → {{ headers[col] }}
              </span>
            </p>
          </div>

          <div class="image-base-url-block">
            <label for="image-base-url">Базовый URL для изображений (необязательно)</label>
            <input
              id="image-base-url"
              v-model.trim="imageBaseUrl"
              type="url"
              placeholder="https://site.com"
            >
            <p class="hint">
              Если в файле путь вида <code>catalog/..../image.jpg</code>, укажи домен и он будет подставлен автоматически.
            </p>
          </div>

          <div class="import-history" v-if="importHistory.length">
            <h4>Последние импорты</h4>
            <div class="history-list">
              <div v-for="run in importHistory" :key="run.id" class="history-item">
                <div>
                  <strong>#{{ run.id }}</strong>
                  <span class="status-badge" :class="`status-${run.status}`">{{ getStatusLabel(run.status) }}</span>
                </div>
                <div class="history-meta">
                  <span>{{ run.source_filename || 'Файл не указан' }}</span>
                  <span v-if="run.imported_count !== undefined">Импортировано: {{ run.imported_count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="actions">
          <button @click="step = 1" class="btn-secondary">Назад</button>
          <button @click="startImport" class="btn-primary" :disabled="!isMappingValid">
            Импортировать
          </button>
        </div>
      </div>

      <!-- Шаг 3: Результат импорта -->
      <div v-if="step === 3" class="step">
        <h3>Результат импорта</h3>
        
        <div class="result" :class="{ 'success': importResult.success, 'error': !importResult.success }">
          <div class="result-icon">{{ importResult.success ? '✅' : '❌' }}</div>
          <div class="result-message">{{ importResult.message }}</div>

          <p v-if="importResult.import_run_status" class="run-status">
            Статус: <strong>{{ getStatusLabel(importResult.import_run_status) }}</strong>
          </p>
          
          <div v-if="importResult.success_count !== undefined" class="stats">
            <p>✅ Успешно импортировано: {{ importResult.success_count }}</p>
            <p v-if="importResult.total_rows">📊 Всего строк: {{ importResult.total_rows }}</p>
            <p v-if="importResult.limit !== undefined">📦 Лимит тарифа: {{ importResult.limit }}</p>
            <p v-if="importResult.current_count_before_import !== undefined">
              До импорта в магазине было: {{ importResult.current_count_before_import }}
            </p>
            <p v-if="importResult.available_slots_before_import !== undefined">
              Доступно слотов до импорта: {{ importResult.available_slots_before_import }}
            </p>
            <p v-if="importResult.skipped_due_to_limit > 0">
              ⛔ Пропущено из-за лимита: {{ importResult.skipped_due_to_limit }}
            </p>
          </div>

          <div v-if="importResult.errors && importResult.errors.length" class="errors">
            <h4>Ошибки:</h4>
            <div v-for="(error, idx) in importResult.errors" :key="idx" class="error-item">
              <strong>Строка {{ error.row }}:</strong>
              <ul>
                <li v-for="err in error.errors" :key="err">{{ err }}</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="actions">
          <button @click="$emit('close')" class="btn-primary">Закрыть</button>
          <button
            v-if="importResult.import_run_id && importResult.import_run_status !== 'completed' && importResult.import_run_status !== 'failed'"
            @click="refreshImportStatus"
            class="btn-secondary"
          >
            Обновить статус
          </button>
          <button v-if="importResult.success && importResult.import_run_status === 'completed'" @click="reloadProducts" class="btn-secondary">
            Обновить список
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch, onBeforeUnmount } from 'vue'
import axios from 'axios'

export default {
  name: 'AdvancedImportModal',
  props: {
    shopId: {
      type: [String, Number],
      required: true
    }
  },
  emits: ['close', 'import-complete'],
  setup(props, { emit }) {
    const step = ref(1)
    const loading = ref(false)
    const dragover = ref(false)
    const selectedFile = ref(null)
    const headers = ref([])
    const sampleData = ref([])
    const detectedMapping = ref(null)
    const importResult = ref(null)
    const imageBaseUrl = ref('')
    const importHistory = ref([])
    const importPollTimer = ref(null)
    
    const mapping = reactive({
      name: null,
      price: null,
      description: null,
      category: null,
      in_stock: null,
      image: null
    })

    const mappingFields = [
      { key: 'name', label: 'Название', required: true },
      { key: 'price', label: 'Цена', required: true },
      { key: 'description', label: 'Описание', required: false },
      { key: 'category', label: 'Категория', required: false },
      { key: 'in_stock', label: 'Наличие', required: false },
      { key: 'image', label: 'Изображение (URL)', required: false }
    ]

    const isMappingValid = computed(() => {
      return mapping.name !== null && mapping.price !== null
    })

    const formatFileSize = (bytes) => {
      if (bytes < 1024) return bytes + ' B'
      if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
      return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
    }

    const handleFileSelect = (event) => {
      selectedFile.value = event.target.files[0]
    }

    const handleDrop = (event) => {
      dragover.value = false
      const file = event.dataTransfer.files[0]
      if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls') || file.name.endsWith('.csv'))) {
        selectedFile.value = file
      } else {
        alert('Пожалуйста, загрузите файл в формате .xlsx, .xls или .csv')
      }
    }

    const getSampleValue = (columnIndex) => {
      if (columnIndex !== null && sampleData.value[columnIndex]) {
        return sampleData.value[columnIndex]
      }
      return ''
    }

    const getStatusLabel = (status) => {
      switch (status) {
        case 'pending':
          return 'В очереди'
        case 'processing':
          return 'Обрабатывается'
        case 'completed':
          return 'Завершен'
        case 'failed':
          return 'Ошибка'
        default:
          return status || 'Неизвестно'
      }
    }

    const stopImportPolling = () => {
      if (importPollTimer.value) {
        clearInterval(importPollTimer.value)
        importPollTimer.value = null
      }
    }

    const applyRunStatus = (run) => {
      if (!run) return

      importResult.value = {
        success: run.status !== 'failed',
        message:
          run.status === 'completed'
            ? `Импорт завершен. Успешно импортировано ${run.success_count || 0} товаров`
            : run.status === 'failed'
              ? (run.error_message || 'Импорт завершился с ошибкой')
              : 'Импорт выполняется в фоне',
        import_run_id: run.id,
        import_run_status: run.status,
        success_count: run.success_count ?? run.imported_count ?? 0,
        imported_count: run.imported_count ?? 0,
        failed_count: run.failed_count ?? 0,
        total_rows: run.total_rows ?? 0,
        skipped_due_to_limit: run.skipped_due_to_limit ?? 0,
        limit: run.limit,
        current_count_before_import: run.current_count_before_import,
        available_slots_before_import: run.available_slots_before_import,
        errors: run.failures || [],
      }
    }

    const refreshImportStatus = async () => {
      if (!importResult.value?.import_run_id) return

      try {
        const response = await axios.get(
          `/api/shops/${props.shopId}/import/status/${importResult.value.import_run_id}`
        )
        const run = response.data?.import_run
        applyRunStatus(run)

        if (run?.status === 'completed' || run?.status === 'failed') {
          stopImportPolling()
          await fetchImportHistory()
          if (run.status === 'completed') {
            emit('import-complete')
          }
        }
      } catch (error) {
        console.error('Ошибка обновления статуса импорта:', error)
      }
    }

    const startImportPolling = (runId) => {
      stopImportPolling()
      if (!runId) return

      importPollTimer.value = setInterval(() => {
        refreshImportStatus()
      }, 2500)
    }

    const fetchImportHistory = async () => {
      try {
        const response = await axios.get(`/api/shops/${props.shopId}/import/history`, {
          params: { per_page: 5 }
        })
        importHistory.value = response.data?.runs?.data || []
      } catch (error) {
        console.error('Ошибка загрузки истории импорта:', error)
      }
    }

    const previewFile = async () => {
      if (!selectedFile.value) return

      loading.value = true
      const formData = new FormData()
      formData.append('file', selectedFile.value)

    console.log('Отправляю файл:', selectedFile.value)
  console.log('FormData entries:', [...formData.entries()])

      try {
        const response = await axios.post(`/api/shops/${props.shopId}/import/preview`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (response.data.success) {
          headers.value = response.data.headers
          sampleData.value = response.data.sample_data
          detectedMapping.value = response.data.detected_mapping
          
          // Применяем обнаруженный маппинг
          Object.assign(mapping, response.data.detected_mapping)
          
          step.value = 2
          await fetchImportHistory()
        } else {
          alert(response.data.message)
        }
      } catch (error) {
        console.error('Ошибка предпросмотра:', error)
        alert(error.response?.data?.message || 'Ошибка при загрузке файла')
      } finally {
        loading.value = false
      }
    }

    const runSyncFallbackImport = async (formData) => {
      const response = await axios.post(`/api/shops/${props.shopId}/import`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })

      importResult.value = {
        ...response.data,
        import_run_status: response.data.success ? 'completed' : 'failed',
      }
      step.value = 3

      if (response.data.success) {
        emit('import-complete')
      }
    }

    const startImport = async () => {
      if (!isMappingValid.value) {
        alert('Пожалуйста, укажите название и цену товара')
        return
      }

      loading.value = true
      stopImportPolling()

      const formData = new FormData()
      formData.append('file', selectedFile.value)

      const mappingArray = {}
      Object.keys(mapping).forEach(key => {
        if (mapping[key] !== null) {
          mappingArray[key] = mapping[key]
        }
      })
      formData.append('mapping', JSON.stringify(mappingArray))
      formData.append('image_base_url', imageBaseUrl.value || '')

      try {
        const asyncResponse = await axios.post(`/api/shops/${props.shopId}/import/async`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })

        if (asyncResponse.data?.success && asyncResponse.data?.import_run?.id) {
          importResult.value = {
            success: true,
            message: 'Импорт поставлен в очередь. Ожидаем выполнение...',
            import_run_id: asyncResponse.data.import_run.id,
            import_run_status: asyncResponse.data.import_run.status,
            limit: asyncResponse.data.limit,
            current_count_before_import: asyncResponse.data.current_count_before_import,
            available_slots_before_import: asyncResponse.data.available_slots_before_import,
          }
          step.value = 3
          await fetchImportHistory()
          startImportPolling(asyncResponse.data.import_run.id)
          await refreshImportStatus()
        } else {
          await runSyncFallbackImport(formData)
        }
      } catch (error) {
        const statusCode = error?.response?.status

        // fallback на старый синхронный endpoint, если async endpoint недоступен
        if (statusCode === 404 || statusCode === 405) {
          try {
            await runSyncFallbackImport(formData)
          } catch (fallbackError) {
            console.error('Ошибка fallback импорта:', fallbackError)
            importResult.value = fallbackError.response?.data || {
              success: false,
              message: 'Ошибка при импорте'
            }
            step.value = 3
          }
        } else {
          console.error('Ошибка async импорта:', error)
          importResult.value = error.response?.data || {
            success: false,
            message: 'Ошибка при импорте'
          }
          step.value = 3
        }
      } finally {
        loading.value = false
      }
    }

    const reloadProducts = () => {
      emit('import-complete')
      emit('close')
    }

    // Сброс состояния при закрытии
    watch(() => step.value, (newVal) => {
      if (newVal === 1) {
        selectedFile.value = null
        headers.value = []
        sampleData.value = []
        detectedMapping.value = null
        importResult.value = null
        importHistory.value = []
        stopImportPolling()
        imageBaseUrl.value = ''
        Object.keys(mapping).forEach(key => mapping[key] = null)
      }
    })

    onBeforeUnmount(() => {
      stopImportPolling()
    })

    return {
      step,
      loading,
      dragover,
      selectedFile,
      headers,
      sampleData,
      detectedMapping,
      importResult,
      imageBaseUrl,
      importHistory,
      mapping,
      mappingFields,
      isMappingValid,
      getStatusLabel,
      formatFileSize,
      handleFileSelect,
      handleDrop,
      getSampleValue,
      previewFile,
      startImport,
      refreshImportStatus,
      reloadProducts
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
}

.close-btn:hover {
  color: #333;
}

.step {
  padding: 1.5rem;
}

.upload-area {
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
  margin: 1rem 0;
}

.upload-area:hover {
  border-color: #4CAF50;
  background: #f9f9f9;
}

.upload-area.dragover {
  border-color: #4CAF50;
  background: #e8f5e9;
}

.upload-content .icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 1rem;
}

.upload-content .hint {
  color: #999;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.selected-file {
  padding: 0.5rem;
  background: #f5f5f5;
  border-radius: 4px;
  margin: 1rem 0;
}

.file-size {
  color: #666;
  font-size: 0.9rem;
  margin-left: 0.5rem;
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-primary {
  background: #4CAF50;
  color: white;
  border: none;
  padding: 0.5rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

.btn-primary:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  padding: 0.5rem 1.5rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

.btn-secondary:hover {
  background: #e8e8e8;
}

.loading {
  text-align: center;
  padding: 2rem;
  color: #666;
}

.mapping-container {
  margin: 1rem 0;
}

.preview-table {
  overflow-x: auto;
}

.preview-table table {
  width: 100%;
  border-collapse: collapse;
}

.preview-table th {
  text-align: left;
  padding: 0.75rem;
  background: #f5f5f5;
  font-weight: 600;
}

.preview-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #eee;
}

.preview-table select {
  width: 100%;
  padding: 0.25rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.required {
  color: #f44336;
  margin-left: 0.25rem;
}

.sample {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #666;
  font-size: 0.9rem;
}

.detected-info {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #e3f2fd;
  border-radius: 4px;
  font-size: 0.9rem;
}

.import-history {
  margin-top: 1rem;
  border: 1px solid #e5e9f1;
  border-radius: 6px;
  padding: 0.75rem;
  background: #fff;
}

.import-history h4 {
  margin: 0 0 0.5rem;
}

.history-list {
  display: grid;
  gap: 0.5rem;
}

.history-item {
  border: 1px solid #edf1f7;
  border-radius: 6px;
  padding: 0.55rem 0.65rem;
}

.history-meta {
  margin-top: 0.35rem;
  display: flex;
  flex-direction: column;
  font-size: 0.85rem;
  color: #667085;
}

.status-badge {
  margin-left: 0.5rem;
  font-size: 0.78rem;
  padding: 0.15rem 0.4rem;
  border-radius: 999px;
  background: #eef2ff;
  color: #3f4a7a;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-processing {
  background: #dbeafe;
  color: #1e40af;
}

.status-completed {
  background: #dcfce7;
  color: #166534;
}

.status-failed {
  background: #fee2e2;
  color: #991b1b;
}

.image-base-url-block {
  margin-top: 1rem;
  padding: 0.9rem;
  border: 1px solid #e5e9f1;
  border-radius: 6px;
  background: #fafcff;
}

.image-base-url-block label {
  display: block;
  margin-bottom: 0.45rem;
  font-weight: 600;
}

.image-base-url-block input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d8dfeb;
  border-radius: 4px;
}

.image-base-url-block .hint {
  margin-top: 0.45rem;
  color: #667085;
  font-size: 0.86rem;
}

.result {
  text-align: center;
  padding: 2rem;
  margin: 1rem 0;
  border-radius: 8px;
}

.result.success {
  background: #d4edda;
  color: #155724;
}

.result.error {
  background: #f8d7da;
  color: #721c24;
}

.result-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.result-message {
  font-size: 1.2rem;
  font-weight: 500;
  margin-bottom: 1rem;
}

.run-status {
  margin: 0.35rem 0 0.9rem;
}

.stats {
  text-align: left;
  margin: 1rem 0;
  padding: 1rem;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}

.errors {
  text-align: left;
  margin-top: 1rem;
  max-height: 300px;
  overflow-y: auto;
}

.error-item {
  margin: 0.5rem 0;
  padding: 0.5rem;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 4px;
}

.error-item ul {
  margin: 0.5rem 0 0;
  padding-left: 1.5rem;
}

@media (max-width: 768px) {
  .modal-content {
    width: 95%;
    margin: 1rem;
  }
  
  .preview-table td, .preview-table th {
    padding: 0.5rem;
  }
}
</style>
