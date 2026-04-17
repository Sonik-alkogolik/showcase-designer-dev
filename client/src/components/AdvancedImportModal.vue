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
          <button v-if="importResult.success" @click="reloadProducts" class="btn-secondary">
            Обновить список
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue'
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

    const startImport = async () => {
  console.log('Current mapping object:', mapping)
  console.log('Mapping as JSON:', JSON.stringify(mapping))
  
  if (!isMappingValid.value) {
    alert('Пожалуйста, укажите название и цену товара')
    return
  }

  loading.value = true
  const formData = new FormData()
  formData.append('file', selectedFile.value)
  
  // Преобразуем объект в массив вида [поле => индекс колонки]
  const mappingArray = {};
  Object.keys(mapping).forEach(key => {
    if (mapping[key] !== null) {
      mappingArray[key] = mapping[key];
    }
  });
  formData.append('mapping', JSON.stringify(mappingArray));

  try {
    const response = await axios.post(`/api/shops/${props.shopId}/import`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    importResult.value = response.data
    step.value = 3

    if (response.data.success) {
      emit('import-complete')
    }
  } catch (error) {
    console.error('Ошибка импорта:', error)
    console.error('Ответ сервера:', error.response?.data)
    importResult.value = error.response?.data || {
      success: false,
      message: 'Ошибка при импорте'
    }
    step.value = 3
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
        Object.keys(mapping).forEach(key => mapping[key] = null)
      }
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
      mapping,
      mappingFields,
      isMappingValid,
      formatFileSize,
      handleFileSelect,
      handleDrop,
      getSampleValue,
      previewFile,
      startImport,
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
