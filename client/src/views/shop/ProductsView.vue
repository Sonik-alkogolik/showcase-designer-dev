<template>
  <div class="products-container">
   <div class="header">
      <h1>Товары магазина "{{ shopName }}"</h1>
      <div class="header-buttons">
        <button @click="showCategoriesModal = true" class="btn-categories">
          📁 Категории (ред)
        </button>
        <button
          @click="openImportModal"
          class="btn-secondary"
          :disabled="!canImportExcel"
          :title="importRestrictionMessage"
        >
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
      <option v-for="category in categories" :key="category.id" :value="category.id">
        {{ category.name }}
      </option>
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
          <p class="price">{{ formatPrice(product.price) }} ₽</p>

          <div v-if="cleanDescription(product.description)" class="description-block">
            <button
              type="button"
              class="description-toggle"
              @click="toggleDescription(product.id)"
            >
              <span>Описание</span>
              <span class="description-arrow" :class="{ open: isDescriptionOpen(product.id) }">⌄</span>
            </button>

            <transition name="fade">
              <div
                v-if="isDescriptionOpen(product.id)"
                class="description"
              >
                {{ cleanDescription(product.description) }}
              </div>
            </transition>
          </div>

          <p class="category">Категория: {{ product.category_name || 'Без категории' }}</p>

          <p class="stock" :class="{ 'in-stock': product.in_stock }">
            {{ product.in_stock ? 'В наличии' : 'Нет в наличии' }}
          </p>

          <p v-if="product.show_in_slider" class="slider-badge">В слайдере</p>

          <div
            v-if="getVisibleAttributes(product).length"
            class="product-attributes"
          >
            <div
              v-for="attribute in getVisibleAttributes(product)"
              :key="attribute.key"
              class="attribute-item"
            >
              <span class="attribute-name">{{ attribute.key }}:</span>
              <span class="attribute-value">{{ attribute.value }}</span>
            </div>
          </div>

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
          <div class="form-group">
            <label>Или загрузить изображение</label>
            <input type="file" accept="image/*" @change="onImageFileChange">
            <p v-if="selectedImageFileName" class="upload-note">Выбрано: {{ selectedImageFileName }}</p>
          </div>
          <div class="form-group checkbox">
            <label>
              <input type="checkbox" v-model="form.in_stock">
              В наличии
            </label>
          </div>
          <div class="form-group checkbox">
            <label>
              <input type="checkbox" v-model="form.show_in_slider">
              Добавить в слайдер
            </label>
          </div>
          <!-- Атрибуты товара -->
          <div class="attributes-section">
            <h3>Дополнительные характеристики</h3>
            
            <!-- Список существующих атрибутов -->
            <div v-for="(value, key) in editingAttributes" :key="key" class="attribute-row">
              <input 
                type="text" 
                v-model="editingAttributes[key]"
                :placeholder="key"
                class="attribute-value"
              >
              <button @click="removeAttribute(key)" type="button" class="btn-remove-attr">✕</button>
            </div>
            
            <!-- Добавление нового атрибута -->
            <div class="add-attribute">
              <input 
                type="text" 
                v-model="newAttributeKey" 
                placeholder="Название (например: цвет)"
                class="attribute-key"
              >
              <input 
                type="text" 
                v-model="newAttributeValue" 
                placeholder="Значение"
                class="attribute-value"
              >
              <button @click="addAttribute" type="button" class="btn-add-attr">+ Добавить</button>
            </div>
          </div>
          <div class="form-actions">
            <button type="submit" class="btn-primary">Сохранить</button>
            <button type="button" @click="closeModal" class="btn-secondary">Отмена</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно для управления категориями -->
    <div v-if="showCategoriesModal" class="modal">
      <div class="modal-content categories-modal">
        <h2>Управление категориями</h2>
        
       <!-- Список существующих категорий -->
        <div class="categories-list">
          <div v-for="category in categories" :key="category.id" class="category-item">
            <span class="category-name">{{ category.name }}</span>
            <div class="category-actions">
              <button @click="editCategory(category)" class="btn-icon btn-edit">✏️</button>
              <button @click="deleteCategory(category)" class="btn-icon btn-delete">🗑️</button>
            </div>
          </div>
          <div v-if="!categories.length" class="empty-categories">
            Нет созданных категорий
          </div>
        </div>

        <!-- Форма добавления/редактирования категории -->
        <div class="category-form">
          <h3>{{ editingCategory ? 'Редактировать' : 'Добавить' }} категорию</h3>
          <div class="form-group">
            <input 
              v-model="categoryForm.name" 
              placeholder="Название категории"
              @keyup.enter="saveCategory"
            >
          </div>
          <div class="form-actions">
            <button @click="saveCategory" class="btn-primary">
              {{ editingCategory ? 'Сохранить' : 'Добавить' }}
            </button>
            <button v-if="editingCategory" @click="cancelEditCategory" class="btn-secondary">
              Отмена
            </button>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeCategoriesModal" class="btn-secondary">Закрыть</button>
        </div>
      </div>
    </div>

    <!-- Модальное окно для умного импорта -->
    <AdvancedImportModal 
      v-if="showImportModal"
      :shop-id="shopId"
      @close="closeImportModal"
      @import-complete="loadProducts"
    />
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import debounce from 'lodash/debounce'
import AdvancedImportModal from '@/components/AdvancedImportModal.vue'

export default {
  name: 'ProductsView',
  components: {
    AdvancedImportModal
  },
  setup() {
    const route = useRoute()
    const shopId = route.params.shopId
    const products = ref([])
    const shopName = ref('')
    const limits = ref(null)
    const categories = ref([])
    const canImportExcel = ref(false)
    const importRestrictionMessage = ref('Импорт из Excel доступен')
    const showCreateForm = ref(false)
    const editingProduct = ref(null)
    const showImportModal = ref(false)
    const showCategoriesModal = ref(false)
    const editingCategory = ref(null)
    const expandedDescriptions = ref({})
    const categoryForm = reactive({
      name: ''
    })
    
    const editingAttributes = ref({})
    const newAttributeKey = ref('')
    const newAttributeValue = ref('')
    const selectedImageFile = ref(null)
    const selectedImageFileName = ref('')
    
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
      in_stock: true,
      show_in_slider: false
    })

    const canCreate = computed(() => {
      return limits.value && limits.value.used < limits.value.total
    })

    const resolveCategoryName = (category) => {
      if (!category) return null
      if (typeof category === 'string') return category
      if (typeof category === 'object' && category.name) return category.name
      return null
    }

    const hiddenAttributeKeys = [
      'product_id',
      'categories',
      'main_category',
      'upc',
      'ean',
      'jan',
      'isbn',
      'mpn',
      'location',
      'points',
      'date_added',
      'date_modified',
      'date_available',
      'weight',
      'weight_unit',
      'length',
      'width',
      'height',
      'length_unit',
      'status',
      'tax_class_id',
      'seo_keyword',
      'meta_title',
      'meta_title(ru-ru)',
      'meta_description',
      'meta_description(ru-ru)',
      'meta_h1',
      'meta_h1(ru-ru)',
      'meta_keywords',
      'meta_keywords(ru-ru)',
      'stock_status',
      'stock_status_id',
      'store_ids',
      'layout',
      'related_ids',
      'sort_order',
      'subtract',
      'minimum',
      'descriptionru_ru',
      'description(ru-ru)',
      'name(ru-ru)',
      'tags(ru-ru)'
]

const normalizeAttributeKey = (key) => {
  return String(key || '')
    .trim()
    .toLowerCase()
}

const cleanDescription = (value) => {
  if (!value) return ''

  return String(value)
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/gi, ' ')
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/gi, "'")
    .replace(/&amp;/gi, '&')
    .replace(/\s+/g, ' ')
    .trim()
}

const isBrokenEncoding = (value) => {
  if (!value) return false

  const text = String(value)
  return /Ð|Ñ|Ã|�/.test(text)
}

const getVisibleAttributes = (product) => {
  const attrs = product?.attributes || {}

  return Object.entries(attrs)
    .filter(([key, value]) => {
      if (value === null || value === undefined || value === '') return false

      const normalizedKey = normalizeAttributeKey(key)
      if (hiddenAttributeKeys.includes(normalizedKey)) return false

      if (isBrokenEncoding(value)) return false

      return true
    })
    .map(([key, value]) => ({
      key,
      value: cleanDescription(value)
    }))
}

    const toggleDescription = (productId) => {
      expandedDescriptions.value = {
        ...expandedDescriptions.value,
        [productId]: !expandedDescriptions.value[productId]
      }
    }

    const isDescriptionOpen = (productId) => {
      return Boolean(expandedDescriptions.value[productId])
    }

    const formatPrice = (price) => {
      const num = Number(price)
      return Number.isFinite(num) ? num.toFixed(2) : price
    }

    const loadProducts = async () => {
      // console.log('🔄 loadProducts started')
      try {
        const response = await axios.get(`/api/shops/${shopId}/products`, {
          params: filters
        })
        // console.log('📦 API Response:', response.data)
        
        products.value = (response.data.products.data || []).map(product => ({
            ...product,
            category_name: resolveCategoryName(product.category)
          }))
        // console.log('✅ products updated:', products.value)
        expandedDescriptions.value = {}
        // Используем категории с сервера
        categories.value = response.data.categories || []
        // console.log('✅ categories from server:', categories.value)
        
        // Обновляем использованное количество товаров
        if (limits.value) {
          limits.value.used = products.value.length
        }
      } catch (error) {
        console.error('❌ Ошибка загрузки товаров:', error)
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
          const capabilities = subscriptionResponse.data.current_capabilities || {}
          const totalLimit = Number(capabilities.products_limit ?? 0)
          canImportExcel.value = Boolean(capabilities.can_import_excel)
          importRestrictionMessage.value = canImportExcel.value
            ? 'Импорт из Excel доступен'
            : 'Импорт из Excel доступен только на платном тарифе'

          limits.value = {
            total: totalLimit,
            used: products.value.length
          }
        } else {
          canImportExcel.value = false
          importRestrictionMessage.value = 'Нет активной подписки'
          limits.value = {
            total: 0,
            used: products.value.length
          }
        }
      } catch (error) {
        console.error('Ошибка загрузки информации о магазине:', error)
      }
    }

    const openImportModal = () => {
      if (!canImportExcel.value) {
        alert(importRestrictionMessage.value || 'Импорт из Excel доступен только на платном тарифе')
        return
      }
      showImportModal.value = true
    }

    const debouncedSearch = debounce(loadProducts, 300)

    const saveProduct = async () => {
      console.log('🔵 saveProduct вызван!')
      console.log('📦 editingProduct:', editingProduct.value)
      console.log('📋 form:', {...form})
      console.log('🏷️ editingAttributes:', {...editingAttributes.value})
      
      try {
        const url = editingProduct.value 
          ? `/api/shops/${shopId}/products/${editingProduct.value.id}`
          : `/api/shops/${shopId}/products`
        
        const method = editingProduct.value ? 'put' : 'post'
        
        console.log('🌐 URL:', url)
        console.log('🔄 Method:', method)
        
        // Добавляем атрибуты к данным формы
        const productData = {
          ...form,
          attributes: editingAttributes.value
        }
        
        console.log('📤 Отправляемые данные:', productData)
        
        let response
        if (selectedImageFile.value) {
          const formData = new FormData()
          Object.entries(productData).forEach(([key, value]) => {
            if (value === null || value === undefined) {
              return
            }
            if (key === 'attributes') {
              formData.append('attributes', JSON.stringify(value || {}))
              return
            }
            if (typeof value === 'boolean') {
              formData.append(key, value ? '1' : '0')
              return
            }
            formData.append(key, String(value))
          })
          formData.append('image_file', selectedImageFile.value)
          const uploadMethod = editingProduct.value ? 'post' : method
          if (editingProduct.value) {
            formData.append('_method', 'PUT')
          }
          response = await axios[uploadMethod](url, formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          })
        } else {
          response = await axios[method](url, productData)
        }
        
        console.log('✅ Ответ сервера:', response.data)
        
        if (response.data.success) {
          await loadProducts()
          closeModal()
          console.log('📌 После закрытия модалки, products:', products.value)
          // Принудительно обновляем компонент
          products.value = [...products.value]
        }
      } catch (error) {
        console.error('❌ Ошибка сохранения товара:', error)
        console.error('📄 Детали ошибки:', error.response?.data)
        alert(error.response?.data?.message || 'Ошибка при сохранении')
      }
    }

    const editProduct = (product) => {
      editingProduct.value = product
      Object.assign(form, {
        ...product,
        category: product.category_name || resolveCategoryName(product.category) || '',
        show_in_slider: Boolean(product.show_in_slider)
      })
      editingAttributes.value = product.attributes ? { ...product.attributes } : {}
    }
    
    const addAttribute = () => {
      if (newAttributeKey.value && newAttributeValue.value) {
        editingAttributes.value[newAttributeKey.value] = newAttributeValue.value
        newAttributeKey.value = ''
        newAttributeValue.value = ''
      }
    }

    const removeAttribute = (key) => {
      delete editingAttributes.value[key]
      // Заставляем Vue обновить представление
      editingAttributes.value = { ...editingAttributes.value }
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
      editingAttributes.value = {}
      selectedImageFile.value = null
      selectedImageFileName.value = ''
      Object.assign(form, {
        name: '',
        price: '',
        description: '',
        category: '',
        image: '',
        in_stock: true,
        show_in_slider: false
      })
    }

    const onImageFileChange = (event) => {
      const file = event?.target?.files?.[0] || null
      selectedImageFile.value = file
      selectedImageFileName.value = file ? file.name : ''
    }

    const closeImportModal = () => {
      showImportModal.value = false
    }

    // Методы для работы с категориями
    const editCategory = (category) => {
      editingCategory.value = category
      categoryForm.name = category.name
    }

    const cancelEditCategory = () => {
      editingCategory.value = null
      categoryForm.name = ''
    }

 const saveCategory = async () => {
  if (!categoryForm.name.trim()) {
    alert('Введите название категории')
    return
  }

  try {
    if (editingCategory.value) {
      // Обновление существующей категории
      const response = await axios.put(
        `/api/shops/${shopId}/categories/${editingCategory.value.id}`,
        { name: categoryForm.name }
      )
      
      if (response.data.success) {
        // Обновляем категорию в списке
        const index = categories.value.findIndex(c => c.id === editingCategory.value.id)
        if (index !== -1) {
          categories.value[index] = response.data.category
        }
        cancelEditCategory()
      }
    } else {
      // Создание новой категории
      const response = await axios.post(`/api/shops/${shopId}/categories`, {
        name: categoryForm.name
      })
      
      if (response.data.success) {
        categories.value.push(response.data.category)
        cancelEditCategory()
      }
    }
  } catch (error) {
    console.error('Ошибка сохранения категории:', error)
    alert(error.response?.data?.message || 'Ошибка при сохранении категории')
  }
}

    const deleteCategory = async (category) => {
  if (!confirm(`Удалить категорию "${category.name}"? Товары с этой категорией станут без категории.`)) return
  
  try {
    const response = await axios.delete(`/api/shops/${shopId}/categories/${category.id}`)
    
    if (response.data.success) {
      // Удаляем категорию из списка
      categories.value = categories.value.filter(c => c.id !== category.id)
      
      // Обновляем товары (убираем категорию у товаров, где она была)
      products.value = products.value.map(p => {
        if (p.category_id === category.id) {
          return { ...p, category_id: null, category: null, category_name: null }
        }
        return p
      })
      
      // Если выбранная в фильтре категория была удалена, сбрасываем фильтр
      if (filters.category === category.id.toString()) {
        filters.category = ''
        await loadProducts()
      }
    }
  } catch (error) {
    console.error('Ошибка удаления категории:', error)
    alert(error.response?.data?.message || 'Ошибка при удалении категории')
  }
}

    const closeCategoriesModal = () => {
      showCategoriesModal.value = false
      cancelEditCategory()
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
      editingAttributes, 
      newAttributeKey,    
      newAttributeValue,  
      form,
      canCreate,
      loadProducts,
      debouncedSearch,
      saveProduct,
      editProduct,
      deleteProduct,
      closeModal,
      showImportModal,
      openImportModal,
      closeImportModal,
      canImportExcel,
      importRestrictionMessage,
      addAttribute,       
      removeAttribute,
      onImageFileChange,
      selectedImageFileName,
      // Новые свойства для категорий
      showCategoriesModal,
      editingCategory,
      categoryForm,
      editCategory,
      cancelEditCategory,
      saveCategory,
      deleteCategory,
      closeCategoriesModal,
      expandedDescriptions,
      toggleDescription,
      isDescriptionOpen,
      getVisibleAttributes,
      cleanDescription,
      formatPrice,
    }
  }
}
</script>

<style scoped>
.products-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header-buttons {
  display: flex;
  gap: 1rem;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.filters input, .filters select {
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  flex: 1;
}

.products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}

.product-card {
  border: 1px solid #eee;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-image {
  height: 200px;
  overflow: hidden;
}

.product-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.product-info {
  padding: 1rem;
}

.product-info h3 {
  margin: 0 0 0.5rem;
  font-size: 1.2rem;
}

.price {
  font-size: 1.3rem;
  font-weight: bold;
  color: #4CAF50;
  margin: 0.5rem 0;
}

.description {
  color: #666;
  margin: 0.5rem 0;
}

.category {
  color: #888;
  font-size: 0.9rem;
}

.stock {
  font-weight: 600;
  color: #f44336;
}

.stock.in-stock {
  color: #4CAF50;
}

.slider-badge {
  display: inline-block;
  margin-top: 0.35rem;
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #07242b;
  background: linear-gradient(120deg, #4ad8ff, #5effc3);
}

.actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  margin-top: 1rem;
}

.btn-edit, .btn-delete {
  padding: 0.25rem 0.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1.2rem;
}

.btn-edit {
  background: #2196f3;
  color: white;
}

.btn-delete {
  background: #f44336;
  color: white;
}

.btn-categories {
  background: #9c27b0;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-categories:hover {
  background: #7b1fa2;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  max-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.categories-modal {
  max-width: 600px;
}

.categories-list {
  margin: 1.5rem 0;
  border: 1px solid #eee;
  border-radius: 4px;
  max-height: 300px;
  overflow-y: auto;
}

.category-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #eee;
}

.category-item:last-child {
  border-bottom: none;
}

.category-name {
  font-size: 1rem;
  color: #333;
}

.category-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  padding: 0.25rem 0.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

.empty-categories {
  padding: 2rem;
  text-align: center;
  color: #999;
}

.category-form {
  margin: 1.5rem 0;
  padding: 1rem;
  background: #f5f5f5;
  border-radius: 4px;
}

.category-form h3 {
  margin-bottom: 1rem;
  font-size: 1.1rem;
  color: #333;
}

.modal-footer {
  margin-top: 1.5rem;
  text-align: right;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.form-group input, .form-group textarea, .form-group select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.upload-note {
  margin-top: 0.4rem;
  font-size: 0.85rem;
  color: #8ecbff;
}

.form-group.checkbox label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: normal;
}

.form-group.checkbox input {
  width: auto;
}

.form-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1.5rem;
}

.btn-primary {
  background: #4CAF50;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}

.btn-secondary {
  background: #f5f5f5;
  color: #333;
  border: 1px solid #ddd;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #999;
}

.limits-info {
  background: #e3f2fd;
  padding: 1rem;
  border-radius: 4px;
  margin-bottom: 1rem;
}

.product-attributes {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #eee;
  font-size: 0.9rem;
}
.description-block {
  margin: 0.75rem 0;
}

.description-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f7f9fc;
  border: 1px solid #e3e8ef;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 600;
  color: #2c3e50;
}

.description-arrow {
  transition: transform 0.2s ease;
  font-size: 1.1rem;
  line-height: 1;
}

.description-arrow.open {
  transform: rotate(180deg);
}

.description {
  margin-top: 0.6rem;
  color: #555;
  line-height: 1.5;
  white-space: normal;
  word-break: break-word;
  overflow-wrap: anywhere;
}

.product-info h3,
.description,
.attribute-name,
.attribute-value,
.category,
.stock,
.price {
  word-break: break-word;
  overflow-wrap: anywhere;
}

.product-attributes {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid #eee;
  font-size: 0.9rem;
}

.attribute-item {
  display: flex;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.4rem;
  align-items: flex-start;
}

.attribute-name {
  color: #666;
  font-weight: 600;
  flex: 0 0 42%;
}

.attribute-value {
  color: #333;
  flex: 1;
  text-align: right;
  white-space: normal;
  word-break: break-word;
  overflow-wrap: anywhere;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.18s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.attribute-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.attribute-name {
  color: #666;
  font-weight: 500;
}

.attribute-value {
  color: #333;
  max-width: 60%;
  text-align: right;
}

.attributes-section {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 2px solid #eee;
}

.attributes-section h3 {
  font-size: 1.1rem;
  margin-bottom: 1rem;
  color: #333;
}

.attribute-row {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  align-items: center;
}

.attribute-row .attribute-value {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.btn-remove-attr {
  background: #f44336;
  color: white;
  border: none;
  width: 30px;
  height: 30px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1.2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-remove-attr:hover {
  background: #d32f2f;
}

.add-attribute {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.add-attribute .attribute-key {
  width: 200px;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.add-attribute .attribute-value {
  flex: 1;
  min-width: 200px;
  padding: 0.5rem;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.btn-add-attr {
  background: #4CAF50;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  white-space: nowrap;
}

.btn-add-attr:hover {
  background: #45a049;
}

@media (max-width: 768px) {
  .add-attribute {
    flex-direction: column;
    align-items: stretch;
  }
  
  .add-attribute .attribute-key,
  .add-attribute .attribute-value {
    width: 100%;
  }
  
  .btn-add-attr {
    width: 100%;
  }
  
  .header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .header-buttons {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .filters {
    flex-direction: column;
  }
}
</style>
