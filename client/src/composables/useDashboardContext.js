import { computed, ref } from 'vue'
import axios from 'axios'

const shops = ref([])
const shopsLoading = ref(false)
const shopsLoaded = ref(false)
const selectedShopId = ref(localStorage.getItem('dashboard_selected_shop_id') || '')

const persistSelectedShop = (value) => {
  selectedShopId.value = value ? String(value) : ''
  localStorage.setItem('dashboard_selected_shop_id', selectedShopId.value)
}

const normalizeShops = (payload) => {
  if (Array.isArray(payload)) return payload
  return []
}

export const useDashboardContext = () => {
  const selectedShop = computed(() => {
    return shops.value.find((shop) => String(shop.id) === String(selectedShopId.value)) || null
  })

  const hasShops = computed(() => shops.value.length > 0)

  const loadShops = async () => {
    if (shopsLoading.value) return

    shopsLoading.value = true
    try {
      const response = await axios.get('/api/shops')
      shops.value = normalizeShops(response.data?.shops)

      const hasCurrent = shops.value.some((shop) => String(shop.id) === String(selectedShopId.value))
      if (!hasCurrent) {
        persistSelectedShop(shops.value[0]?.id || '')
      }
      shopsLoaded.value = true
    } catch (error) {
      console.error('Failed to load dashboard shops:', error)
      shops.value = []
      persistSelectedShop('')
    } finally {
      shopsLoading.value = false
    }
  }

  const setSelectedShopId = (shopId) => {
    persistSelectedShop(shopId)
  }

  return {
    shops,
    shopsLoading,
    shopsLoaded,
    hasShops,
    selectedShopId,
    selectedShop,
    loadShops,
    setSelectedShopId,
  }
}

