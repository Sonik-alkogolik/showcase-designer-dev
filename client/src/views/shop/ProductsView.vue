<template>
  <div class="products-container">
   <div class="header">
      <h1>Товары магазина "{{ shopName }}"</h1>
      <div class="header-buttons">
        <button @click="showCategoriesModal = true" class="btn-categories">
          📁 Категории (ред)
        </button>
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
          <p class="price">{{ product.price }} ₽</p>
          <p class="description">{{ product.description }}</p>
          <p class="category">Категория: {{ product.category_name || 'Без категории' }}</p>
          <p class="stock" :class="{ 'in-stock': product.in_stock }">
            {{ product.in_stock ? 'В наличии' : 'Нет в наличии' }}
          </p>
          <p v-if="product.show_in_slider" class="slider-badge">В слайдере</p>
          <div v-if="product.attributes && Object.keys(product.attributes).length" class="product-attributes">
            <div v-for="(value, key) in product.attributes" :key="key" class="attribute-item">
              <span class="attribute-name">{{ key }}:</span>
              <span class="attribute-value">{{ value }}</span>
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
    const showCreateForm = ref(false)
    const editingProduct = ref(null)
    const showImportModal = ref(false)
    const showCategoriesModal = ref(false)
    const editingCategory = ref(null)
    
    const categoryForm = reactive({
      name: ''
    })
    
    const editingAttributes = ref({})
    const newAttributeKey = ref('')
    const newAttributeValue = ref('')
    
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

    const loadProducts = async () => {
      console.log('🔄 loadProducts started')
      try {
        const response = await axios.get(`/api/shops/${shopId}/products`, {
          params: filters
        })
        console.log('📦 API Response:', response.data)
        
        products.value = (response.data.products.data || []).map(product => ({
          ...product,
          category_name: resolveCategoryName(product.category)
        }))
        console.log('✅ products updated:', products.value)
        
        // Используем категории с сервера
        categories.value = response.data.categories || []
        console.log('✅ categories from server:', categories.value)
        
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
        
        const response = await axios[method](url, productData)
        
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
      closeImportModal,
      addAttribute,       
      removeAttribute,
      // Новые свойства для категорий
      showCategoriesModal,
      editingCategory,
      categoryForm,
      editCategory,
      cancelEditCategory,
      saveCategory,
      deleteCategory,
      closeCategoriesModal
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
