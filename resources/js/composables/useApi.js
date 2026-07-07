import { createFetch } from '@vueuse/core'
import { destr } from 'destr'
import { handleApiError } from '@/utils/api'

export const useApi = createFetch({
  baseUrl: import.meta.env.VITE_API_BASE_URL || '/api',
  fetchOptions: {
    headers: {
      Accept: 'application/json',
    },
  },
  options: {
    refetch: true,
    async beforeFetch({ options }) {
      const accessToken = useCookie('accessToken').value
      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,
        }
      }

      if (typeof options.body === 'string' && options.method && options.method !== 'GET') {
        options.headers = {
          ...options.headers,
          'Content-Type': 'application/json',
        }
      }

      return { options }
    },
    afterFetch(ctx) {
      const { data, response } = ctx

      let parsedData = null
      try {
        parsedData = destr(data)
      }
      catch (error) {
        console.error(error)
      }

      return { data: parsedData, response }
    },
    onFetchError(ctx) {
      let parsedData = null
      try {
        parsedData = destr(ctx.data)
      }
      catch (error) {
        console.error(error)
      }

      handleApiError(ctx.response?.status, parsedData?.errors)

      return ctx
    },
  },
})
