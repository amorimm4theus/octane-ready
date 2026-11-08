// Read-heavy API traffic: product detail + paginated listing.
// Usage: docker run --rm --network host -v $PWD/k6:/k6 grafana/k6 run -e BASE=http://localhost:8001 /k6/read-heavy.js
import http from 'k6/http';
import { check } from 'k6';

const BASE = __ENV.BASE || 'http://localhost:8001';
const headers = { 'X-Tenant': 'acme', 'Accept': 'application/json' };

export const options = {
  vus: Number(__ENV.VUS || 50),
  duration: __ENV.DURATION || '30s',
  summaryTrendStats: ['avg', 'p(50)', 'p(95)', 'p(99)'],
};

// SCENARIO=detail  -> GET /products/{id}     (one indexed query: framework overhead dominates)
// SCENARIO=listing -> GET /products?page=N   (paginate = 2 queries: database weighs more)
const SCENARIO = __ENV.SCENARIO || 'detail';

export default function () {
  if (SCENARIO === 'detail') {
    const id = Math.floor(Math.random() * 1000) + 1; // tenant "acme" owns ids 1..1000
    const r = http.get(`${BASE}/products/${id}`, { headers });
    check(r, { 'status 200': (res) => res.status === 200 });
  } else {
    const page = Math.floor(Math.random() * 50) + 1;
    const r = http.get(`${BASE}/products?page=${page}`, { headers });
    check(r, { 'status 200': (res) => res.status === 200 });
  }
}
