<template>
  <div class="products-container">
    <div class="header">
      <h1>Товары магазина "{{ shopName }}"</h1>
      <div class="header-buttons">
        <button @click="showImportModal = true" class="btn-secondary">
          Импорт из Excel
        </button>
        <button @click="showCreateForm = true" class="btn-primary" v-if="canCreate">
          Добавить товар
        </button>
      </div>
    </div>

    <!-- Информация о лимитах -->
    <div class="limits-info" v-if="limits">
      <p>Использовано: {{ limits.used }} из {{ limits.total }} товаров</p>
    </div>

    <!-- Фильтры -->
    <div class="filters">
      <input 
        type="text" 
        v-model="filters.search" 
        placeholder="Поиск по названию..."
        @input="debouncedSearch"
      >
      <select v-model="filters.category" @change="loadProducts">
        <option value="">Все категории</option>
        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
      </select>
    </div>

    <!-- Список товаров -->
    <div class="products-grid" v-if="products.length">
      <div v-for="product in products" :key="product.id" class="product-card">
        <div class="product-image" v-if="product.image">
          <img :src="product.image" :alt="product.name">
        </div>
        <div class="product-info">
          <h3>{{ product.name }}</h3>
          <p class="price">{{ product.price }} ₽</p>
          <p class="description">{{ product.description }}</p>
          <p class="category">Категория: {{ product.category || 'Без категории' }}</p>
          <p class="stock" :class="{ 'in-stock': product.in_stock }">
            {{ product.in_stock ? 'В наличии' : 'Нет в наличии' }}
          </p>
          <div class="actions">
            <button @click="editProduct(product)" class="btn-edit">✏️</button>
            <button @click="deleteProduct(product)" class="btn-delete">🗑️</button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <p>Товаров пока нет</p>
    </div>

    <!-- Модальное окно для создания/редактирования товара -->
    <div v-if="showCreateForm || editingProduct" class="modal">
      <div class="modal-content">
        <h2>{{ editingProduct ? 'Редактировать' : 'Создать' }} товар</h2>
        <form @submit.prevent="saveProduct">
          <div class="form-group">
            <label>Название *</label>
            <input v-model="form.name" required>
          </div>
          <div class="form-group">
            <label>Цена *</label>
            <input type="number" v-model="form.price" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label>Описание</label>
            <textarea v-model="form.description" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Категория</label>
            <input v-model="form.category">
          </div>
          <div class="form-group">
            <label>Изображение (URL)</label>
            <input v-model="form.image" placeholder="https://...">
          </div>
          <div class="form-group checkbox">
            <label>
              <input type="checkbox" v-model="form.in_stock">
              В наличии
            </label>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">Сохранить</button>
            <button type="button" @click="closeModal" class="btn-secondary">Отмена</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно для импорта -->
    <div v-if="showImportModal" class="modal">
      <div class="modal-content">
        <h2>Импорт товаров из Excel/CSV</h2>
        
        <div class="import-info">
          <p>Файл должен содержать колонки:</p>
          <ul>
            <li><strong>название</strong> или <strong>name</strong> - обязательно</li>
            <li><strong>цена</strong> или <strong>price</strong> - обязательно</li>
            <li><strong>описание</strong> или <strong>description</strong> - опционально</li>
            <li><strong>категория</strong> или <strong>category</strong> - опционально</li>
            <li><strong>наличие</strong> или <strong>in_stock</strong> - 1/0, да/нет, yes/no</li>
          </ul>
          <p>Поддерживаемые форматы: .xlsx, .xls, .csv (до 10MB)</p>
        </div>

        <div class="form-group">
          <label for="import-file">Выберите файл</label>
          <input 
            type="file" 
            id="import-file" 
            ref="fileInput"
            accept=".xlsx,.xls,.csv"
            @change="handleFileSelect"
          >
        </div>

        <div v-if="importProgress" class="progress-bar">
          <div class="progress-fill" :style="{ width: importProgress + '%' }"></div>
        </div>

        <div v-if="importResult" class="import-result" :class="{ 'success': importResult.success, 'error': !importResult.success }">
          <p>{{ importResult.message }}</p>
          <div v-if="importResult.errors && importResult.errors.length" class="import-errors">
            <h4>Ошибки:</h4>
            <ul>
              <li v-for="(error, idx) in importResult.errors" :key="idx">
                Строка {{ error.row }}: {{ error.errors.join(', ') }}
              </li>
            </ul>
          </div>
        </div>

        <div class="form-actions">
          <button @click="importProducts" class="btn-primary" :disabled="!selectedFile || importing">
            {{ importing ? 'Импорт...' : 'Импортировать' }}
          </button>
          <button @click="closeImportModal" class="btn-secondary">Отмена</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'

export default {
  name: 'ProductsView',
  setup() {
    const route = useRoute()
    const shopId = route.params.shopId
    
    const products = ref([])
    const shopName = ref('')
    const limits = ref(null)
    const categories = ref([])
    const showCreateForm = ref(false)
    const editingProduct = ref(null)
    const showImportModal = ref(false)
    const selectedFile = ref(null)
    const importing = ref(false)
    const importProgress = ref(0)
    const importResult = ref(null)
    
    const filters = reactive({
      search: '',
      category: ''
    })

    const form = reactive({
      name: '',
      price: '',
      description: '',
      category: '',
      image: '',
      in_stock: true
    })

    const canCreate = computed(() => {
      return limits.value && limits.value.used < limits.value.total
    })

    const loadProducts = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/products`, {
          params: filters
        })
        products.value = response.data.products.data || response.data.products || []
        // Собираем уникальные категории
        const cats = new Set(products.value.map(p => p.category).filter(Boolean))
        categories.value = Array.from(cats)
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error)
      }
    }

    const loadShopInfo = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}`)
        shopName.value = response.data.shop.name
        
        // Получаем лимиты
        const userResponse = await axios.get('/api/user')
        const subscriptionResponse = await axios.get('/api/subscription/plans')
        const subscription = subscriptionResponse.data.current_subscription
        
        if (subscription) {
          const limitsMap = {
            'starter': 100,
            'business': 1000,
            'premium': 10000
          }
          limits.value = {
            total: limitsMap[subscription.plan] || 0,
            used: products.value.length
          }
        }
      } catch (error) {
        console.error('Ошибка загрузки информации о магазине:', error)
      }
    }

    const debouncedSearch = debounce(loadProducts, 300)

    const saveProduct = async () => {
      try {
        const url = editingProduct.value 
          ? `/api/shops/${shopId}/products/${editingProduct.value.id}`
          : `/api/shops/${shopId}/products`
        
        const method = editingProduct.value ? 'put' : 'post'
        
        const response = await axios[method](url, form)
        
        if (response.data.success) {
          await loadProducts()
          closeModal()
        }
      } catch (error) {
        console.error('Ошибка сохранения товара:', error)
        alert(error.response?.data?.message || 'Ошибка при сохранении')
      }
    }

    const editProduct = (product) => {
      editingProduct.value = product
      Object.assign(form, product)
    }

    const deleteProduct = async (product) => {
      if (!confirm(`Удалить товар "${product.name}"?`)) return
      
      try {
        await axios.delete(`/api/shops/${shopId}/products/${product.id}`)
        await loadProducts()
      } catch (error) {
        console.error('Ошибка удаления:', error)
      }
    }

    const closeModal = () => {
      showCreateForm.value = false
      editingProduct.value = null
      Object.assign(form, {
        name: '',
        price: '',
        description: '',
        category: '',
        image: '',
        in_stock: true
      })
    }

    const handleFileSelect = (event) => {
      selectedFile.value = event.target.files[0]
      importResult.value = null
    }

    const importProducts = async () => {
      if (!selectedFile.value) {
        alert('Выберите файл')
        return
      }

      importing.value = true
      importProgress.value = 0
      importResult.value = null

      const formData = new FormData()
      formData.append('file', selectedFile.value)

      try {
        const progressInterval = setInterval(() => {
          if (importProgress.value < 90) {
            importProgress.value += 10
          }
        }, 300)

        const response = await axios.post(`/api/shops/${shopId}/products/import`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        clearInterval(progressInterval)
        importProgress.value = 100

        if (response.data.success) {
          importResult.value = {
            success: true,
            message: response.data.message
          }
          await loadProducts()
          setTimeout(() => {
            closeImportModal()
          }, 2000)
        } else {
          importResult.value = {
            success: false,
            message: response.data.message,
            errors: response.data.errors
          }
        }
      } catch (error) {
        importProgress.value = 0
        if (error.response?.data) {
          importResult.value = {
            success: false,
            message: error.response.data.message || 'Ошибка при импорте',
            errors: error.response.data.errors
          }
        } else {
          importResult.value = {
            success: false,
            message: 'Ошибка при импорте'
          }
        }
      } finally {
        importing.value = false
        setTimeout(() => {
          importProgress.value = 0
        }, 1000)
      }
    }

    const closeImportModal = () => {
      showImportModal.value = false
      selectedFile.value = null
      importResult.value = null
      importProgress.value = 0
      if (document.getElementById('import-file')) {
        document.getElementById('import-file').value = ''
      }
    }

    onMounted(() => {
      loadProducts()
      loadShopInfo()
    })

    return {
      shopId,
      shopName,
      products,
      limits,
      categories,
      filters,
      showCreateForm,
      editingProduct,
      showImportModal,
      selectedFile,
      importing,
      importProgress,
      importResult,
      form,
      canCreate,
      loadProducts,
      debouncedSearch,
      saveProduct,
      editProduct,
      deleteProduct,
      closeModal,
      handleFileSelect,
      importProducts,
      closeImportModal
    }
  }
}
</script>


готово 
<template>
  <div class="products-container">
    <div class="header">
      <h1>Товары магазина "{{ shopName }}"</h1>
      <div class="header-buttons">
        <button @click="showImportModal = true" class="btn-secondary">
          Импорт из Excel
        </button>
        <button @click="showCreateForm = true" class="btn-primary" v-if="canCreate">
          Добавить товар
        </button>
      </div>
    </div>

    <!-- Информация о лимитах -->
    <div class="limits-info" v-if="limits">
      <p>Использовано: {{ limits.used }} из {{ limits.total }} товаров</p>
    </div>

    <!-- Фильтры -->
    <div class="filters">
      <input 
        type="text" 
        v-model="filters.search" 
        placeholder="Поиск по названию..."
        @input="debouncedSearch"
      >
      <select v-model="filters.category" @change="loadProducts">
        <option value="">Все категории</option>
        <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
      </select>
    </div>

    <!-- Список товаров -->
    <div class="products-grid" v-if="products.length">
      <div v-for="product in products" :key="product.id" class="product-card">
        <div class="product-image" v-if="product.image">
          <img :src="product.image" :alt="product.name">
        </div>
        <div class="product-info">
          <h3>{{ product.name }}</h3>
          <p class="price">{{ product.price }} ₽</p>
          <p class="description">{{ product.description }}</p>
          <p class="category">Категория: {{ product.category || 'Без категории' }}</p>
          <p class="stock" :class="{ 'in-stock': product.in_stock }">
            {{ product.in_stock ? 'В наличии' : 'Нет в наличии' }}
          </p>
          <div class="actions">
            <button @click="editProduct(product)" class="btn-edit">✏️</button>
            <button @click="deleteProduct(product)" class="btn-delete">🗑️</button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="empty-state">
      <p>Товаров пока нет</p>
    </div>

    <!-- Модальное окно для создания/редактирования товара -->
    <div v-if="showCreateForm || editingProduct" class="modal">
      <div class="modal-content">
        <h2>{{ editingProduct ? 'Редактировать' : 'Создать' }} товар</h2>
        <form @submit.prevent="saveProduct">
          <div class="form-group">
            <label>Название *</label>
            <input v-model="form.name" required>
          </div>
          <div class="form-group">
            <label>Цена *</label>
            <input type="number" v-model="form.price" step="0.01" min="0" required>
          </div>
          <div class="form-group">
            <label>Описание</label>
            <textarea v-model="form.description" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Категория</label>
            <input v-model="form.category">
          </div>
          <div class="form-group">
            <label>Изображение (URL)</label>
            <input v-model="form.image" placeholder="https://...">
          </div>
          <div class="form-group checkbox">
            <label>
              <input type="checkbox" v-model="form.in_stock">
              В наличии
            </label>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">Сохранить</button>
            <button type="button" @click="closeModal" class="btn-secondary">Отмена</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно для импорта -->
    <div v-if="showImportModal" class="modal">
      <div class="modal-content">
        <h2>Импорт товаров из Excel/CSV</h2>
        
        <div class="import-info">
          <p>Файл должен содержать колонки:</p>
          <ul>
            <li><strong>название</strong> или <strong>name</strong> - обязательно</li>
            <li><strong>цена</strong> или <strong>price</strong> - обязательно</li>
            <li><strong>описание</strong> или <strong>description</strong> - опционально</li>
            <li><strong>категория</strong> или <strong>category</strong> - опционально</li>
            <li><strong>наличие</strong> или <strong>in_stock</strong> - 1/0, да/нет, yes/no</li>
          </ul>
          <p>Поддерживаемые форматы: .xlsx, .xls, .csv (до 10MB)</p>
        </div>

        <div class="form-group">
          <label for="import-file">Выберите файл</label>
          <input 
            type="file" 
            id="import-file" 
            ref="fileInput"
            accept=".xlsx,.xls,.csv"
            @change="handleFileSelect"
          >
        </div>

        <div v-if="importProgress" class="progress-bar">
          <div class="progress-fill" :style="{ width: importProgress + '%' }"></div>
        </div>

        <div v-if="importResult" class="import-result" :class="{ 'success': importResult.success, 'error': !importResult.success }">
          <p>{{ importResult.message }}</p>
          <div v-if="importResult.errors && importResult.errors.length" class="import-errors">
            <h4>Ошибки:</h4>
            <ul>
              <li v-for="(error, idx) in importResult.errors" :key="idx">
                Строка {{ error.row }}: {{ error.errors.join(', ') }}
              </li>
            </ul>
          </div>
        </div>

        <div class="form-actions">
          <button @click="importProducts" class="btn-primary" :disabled="!selectedFile || importing">
            {{ importing ? 'Импорт...' : 'Импортировать' }}
          </button>
          <button @click="closeImportModal" class="btn-secondary">Отмена</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'

export default {
  name: 'ProductsView',
  setup() {
    const route = useRoute()
    const shopId = route.params.shopId
    
    const products = ref([])
    const shopName = ref('')
    const limits = ref(null)
    const categories = ref([])
    const showCreateForm = ref(false)
    const editingProduct = ref(null)
    const showImportModal = ref(false)
    const selectedFile = ref(null)
    const importing = ref(false)
    const importProgress = ref(0)
    const importResult = ref(null)
    
    const filters = reactive({
      search: '',
      category: ''
    })

    const form = reactive({
      name: '',
      price: '',
      description: '',
      category: '',
      image: '',
      in_stock: true
    })

    const canCreate = computed(() => {
      return limits.value && limits.value.used < limits.value.total
    })

    const loadProducts = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}/products`, {
          params: filters
        })
        products.value = response.data.products.data || response.data.products || []
        // Собираем уникальные категории
        const cats = new Set(products.value.map(p => p.category).filter(Boolean))
        categories.value = Array.from(cats)
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error)
      }
    }

    const loadShopInfo = async () => {
      try {
        const response = await axios.get(`/api/shops/${shopId}`)
        shopName.value = response.data.shop.name
        
        // Получаем лимиты
        const userResponse = await axios.get('/api/user')
        const subscriptionResponse = await axios.get('/api/subscription/plans')
        const subscription = subscriptionResponse.data.current_subscription
        
        if (subscription) {
          const limitsMap = {
            'starter': 100,
            'business': 1000,
            'premium': 10000
          }
          limits.value = {
            total: limitsMap[subscription.plan] || 0,
            used: products.value.length
          }
        }
      } catch (error) {
        console.error('Ошибка загрузки информации о магазине:', error)
      }
    }

    const debouncedSearch = debounce(loadProducts, 300)

    const saveProduct = async () => {
      try {
        const url = editingProduct.value 
          ? `/api/shops/${shopId}/products/${editingProduct.value.id}`
          : `/api/shops/${shopId}/products`
        
        const method = editingProduct.value ? 'put' : 'post'
        
        const response = await axios[method](url, form)
        
        if (response.data.success) {
          await loadProducts()
          closeModal()
        }
      } catch (error) {
        console.error('Ошибка сохранения товара:', error)
        alert(error.response?.data?.message || 'Ошибка при сохранении')
      }
    }

    const editProduct = (product) => {
      editingProduct.value = product
      Object.assign(form, product)
    }

    const deleteProduct = async (product) => {
      if (!confirm(`Удалить товар "${product.name}"?`)) return
      
      try {
        await axios.delete(`/api/shops/${shopId}/products/${product.id}`)
        await loadProducts()
      } catch (error) {
        console.error('Ошибка удаления:', error)
      }
    }

    const closeModal = () => {
      showCreateForm.value = false
      editingProduct.value = null
      Object.assign(form, {
        name: '',
        price: '',
        description: '',
        category: '',
        image: '',
        in_stock: true
      })
    }

    const handleFileSelect = (event) => {
      selectedFile.value = event.target.files[0]
      importResult.value = null
    }

    const importProducts = async () => {
      if (!selectedFile.value) {
        alert('Выберите файл')
        return
      }

      importing.value = true
      importProgress.value = 0
      importResult.value = null

      const formData = new FormData()
      formData.append('file', selectedFile.value)

      try {
        const progressInterval = setInterval(() => {
          if (importProgress.value < 90) {
            importProgress.value += 10
          }
        }, 300)

        const response = await axios.post(`/api/shops/${shopId}/products/import`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        })

        clearInterval(progressInterval)
        importProgress.value = 100

        if (response.data.success) {
          importResult.value = {
            success: true,
            message: response.data.message
          }
          await loadProducts()
          setTimeout(() => {
            closeImportModal()
          }, 2000)
        } else {
          importResult.value = {
            success: false,
            message: response.data.message,
            errors: response.data.errors
          }
        }
      } catch (error) {
        importProgress.value = 0
        if (error.response?.data) {
          importResult.value = {
            success: false,
            message: error.response.data.message || 'Ошибка при импорте',
            errors: error.response.data.errors
          }
        } else {
          importResult.value = {
            success: false,
            message: 'Ошибка при импорте'
          }
        }
      } finally {
        importing.value = false
        setTimeout(() => {
          importProgress.value = 0
        }, 1000)
      }
    }

    const closeImportModal = () => {
      showImportModal.value = false
      selectedFile.value = null
      importResult.value = null
      importProgress.value = 0
      if (document.getElementById('import-file')) {
        document.getElementById('import-file').value = ''
      }
    }

    onMounted(() => {
      loadProducts()
      loadShopInfo()
    })

    return {
      shopId,
      shopName,
      products,
      limits,
      categories,
      filters,
      showCreateForm,
      editingProduct,
      showImportModal,
      selectedFile,
      importing,
      importProgress,
      importResult,
      form,
      canCreate,
      loadProducts,
      debouncedSearch,
      saveProduct,
      editProduct,
      deleteProduct,
      closeModal,
      handleFileSelect,
      importProducts,
      closeImportModal
    }
  }
}
</script>