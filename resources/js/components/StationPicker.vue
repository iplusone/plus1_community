<template>
  <div class="station-picker">
    <!-- ステップインジケーター -->
    <div class="flex items-center gap-2 mb-6 text-sm">
      <button
        v-for="(step, i) in steps"
        :key="i"
        class="flex items-center gap-1.5 transition-colors"
        :class="currentStep > i ? 'text-blue-600 cursor-pointer hover:text-blue-800' : currentStep === i ? 'text-gray-900 font-semibold' : 'text-gray-400 cursor-default'"
        :disabled="currentStep <= i"
        @click="goToStep(i)"
      >
        <span
          class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
          :class="currentStep > i ? 'bg-blue-600 text-white' : currentStep === i ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-400'"
        >{{ i + 1 }}</span>
        <span class="hidden sm:inline">{{ step }}</span>
        <svg v-if="i < steps.length - 1" class="w-4 h-4 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </button>
    </div>

    <!-- 選択済みバッジ -->
    <div v-if="selectedPref || selectedRoute || selectedStation" class="flex flex-wrap gap-2 mb-4">
      <span v-if="selectedPref" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-sm border border-blue-200">
        {{ selectedPref.name }}
        <button @click="goToStep(0)" class="hover:text-blue-900 ml-1">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </span>
      <svg v-if="selectedPref && selectedRoute" class="w-4 h-4 text-gray-400 self-center" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      <span v-if="selectedRoute" class="inline-flex items-center gap-1 px-3 py-1 bg-green-50 text-green-700 rounded-full text-sm border border-green-200">
        {{ selectedRoute.line_name }}
        <button @click="goToStep(1)" class="hover:text-green-900 ml-1">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </span>
      <svg v-if="selectedRoute && selectedStation" class="w-4 h-4 text-gray-400 self-center" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      <span v-if="selectedStation" class="inline-flex items-center gap-1 px-3 py-1 bg-orange-50 text-orange-700 rounded-full text-sm border border-orange-200 font-semibold">
        🚉 {{ selectedStation.station_name }}駅
        <button @click="reset" class="hover:text-orange-900 ml-1">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </span>
    </div>

    <!-- ローディング -->
    <div v-if="loading" class="flex items-center justify-center py-12 text-gray-400">
      <svg class="animate-spin w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
      </svg>
      読み込み中...
    </div>

    <!-- Step 0: 都道府県 -->
    <Transition name="fade" mode="out-in">
      <div v-if="!loading && currentStep === 0" key="step0">
        <div class="space-y-4">
          <div v-for="region in regions" :key="region.name">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ region.name }}</h3>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="pref in region.prefectures"
                :key="pref.id"
                class="px-3 py-1.5 rounded-lg text-sm border transition-all hover:shadow-sm"
                :class="selectedPref?.id === pref.id
                  ? 'bg-blue-600 text-white border-blue-600'
                  : 'bg-white text-gray-700 border-gray-200 hover:border-blue-400 hover:text-blue-600'"
                @click="selectPref(pref)"
              >{{ pref.name }}</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 1: 路線 -->
      <div v-else-if="!loading && currentStep === 1" key="step1">
        <div v-if="routes.length === 0" class="text-center py-8 text-gray-400">
          この都道府県には路線データがありません
        </div>
        <div v-else>
          <input
            v-model="routeFilter"
            type="text"
            placeholder="路線名で絞り込み..."
            class="w-full mb-4 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
          />
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <button
              v-for="route in filteredRoutes"
              :key="route.id"
              class="text-left px-4 py-3 rounded-lg border transition-all hover:shadow-sm"
              :class="selectedRoute?.id === route.id
                ? 'bg-green-600 text-white border-green-600'
                : 'bg-white text-gray-700 border-gray-200 hover:border-green-400 hover:text-green-700'"
              @click="selectRoute(route)"
            >
              <div class="font-medium">{{ route.line_name }}</div>
              <div class="text-xs mt-0.5" :class="selectedRoute?.id === route.id ? 'text-green-100' : 'text-gray-400'">{{ route.operator_name }}</div>
            </button>
          </div>
        </div>
      </div>

      <!-- Step 2: 駅 -->
      <div v-else-if="!loading && currentStep === 2" key="step2">
        <div v-if="stations.length === 0" class="text-center py-8 text-gray-400">
          駅データがありません
        </div>
        <div v-else>
          <input
            v-model="stationFilter"
            type="text"
            placeholder="駅名で絞り込み..."
            class="w-full mb-4 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-400"
          />
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
            <button
              v-for="station in filteredStations"
              :key="station.id"
              class="px-3 py-2.5 rounded-lg border text-sm font-medium transition-all hover:shadow-sm"
              :class="selectedStation?.id === station.id
                ? 'bg-orange-500 text-white border-orange-500'
                : 'bg-white text-gray-700 border-gray-200 hover:border-orange-400 hover:text-orange-600'"
              @click="selectStation(station)"
            >{{ station.station_name }}</button>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, getCurrentInstance } from 'vue'

const props = defineProps({
  prefCode: { type: String, default: null },
  prefName: { type: String, default: null },
})

const emit = defineEmits(['station-selected'])

const steps = ['都道府県', '路線', '駅']
const currentStep = ref(0)
const loading = ref(false)

const regions = ref([])
const routes = ref([])
const stations = ref([])

const selectedPref = ref(null)
const selectedRoute = ref(null)
const selectedStation = ref(null)

const routeFilter = ref('')
const stationFilter = ref('')

const filteredRoutes = computed(() =>
  routeFilter.value
    ? routes.value.filter(r => r.line_name.includes(routeFilter.value) || r.operator_name?.includes(routeFilter.value))
    : routes.value
)

const filteredStations = computed(() =>
  stationFilter.value
    ? stations.value.filter(s => s.station_name.includes(stationFilter.value))
    : stations.value
)

onMounted(async () => {
  if (props.prefCode) {
    selectedPref.value = { code: props.prefCode, name: props.prefName ?? props.prefCode }
    loading.value = true
    currentStep.value = 1
    const res = await fetch(`/api/station-picker/railways?pref_code=${props.prefCode}`)
    routes.value = await res.json()
    loading.value = false
  } else {
    loading.value = true
    const res = await fetch('/api/station-picker/prefectures')
    regions.value = await res.json()
    loading.value = false
  }
})

async function selectPref(pref) {
  selectedPref.value = pref
  selectedRoute.value = null
  selectedStation.value = null
  routeFilter.value = ''
  stationFilter.value = ''
  loading.value = true
  currentStep.value = 1
  const res = await fetch(`/api/station-picker/railways?pref_code=${pref.code}`)
  routes.value = await res.json()
  loading.value = false
}

async function selectRoute(route) {
  selectedRoute.value = route
  selectedStation.value = null
  stationFilter.value = ''
  loading.value = true
  currentStep.value = 2
  const res = await fetch(`/api/station-picker/stations?railway_route_id=${route.id}`)
  stations.value = await res.json()
  loading.value = false
}

function selectStation(station) {
  selectedStation.value = station
  const payload = {
    id: station.id,
    station_name: station.station_name,
    line_name: selectedRoute.value.line_name,
    operator_name: selectedRoute.value.operator_name,
    pref_name: selectedPref.value.name,
    pref_code: selectedPref.value.code,
  }
  emit('station-selected', payload)
  getCurrentInstance()?.vnode.el?.dispatchEvent(
    new CustomEvent('station-selected', { bubbles: true, detail: payload })
  )
}

function goToStep(step) {
  if (step < currentStep.value) {
    currentStep.value = step
    if (step === 0) {
      selectedPref.value = null
      selectedRoute.value = null
      selectedStation.value = null
    } else if (step === 1) {
      selectedRoute.value = null
      selectedStation.value = null
    }
  }
}

function reset() {
  currentStep.value = 0
  selectedPref.value = null
  selectedRoute.value = null
  selectedStation.value = null
  routeFilter.value = ''
  stationFilter.value = ''
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.fade-enter-from {
  opacity: 0;
  transform: translateX(8px);
}
.fade-leave-to {
  opacity: 0;
  transform: translateX(-8px);
}
</style>
