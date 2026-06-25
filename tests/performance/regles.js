import http from 'k6/http'
import { check, sleep } from 'k6'

export const options = {
  vus: 5,
  duration: '10s',
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<1500'],
  },
}

export default function () {
  const response = http.get('http://127.0.0.1:8000/api/v1/regles', {
    headers: { Accept: 'application/json' },
  })

  check(response, {
    'réponse HTTP 200': (res) => res.status === 200,
  })

  sleep(1)
}
