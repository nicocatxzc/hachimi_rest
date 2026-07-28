# hachimi_rest

为[hachimi](https://github.com/nicocatxzc/hachimi) headless WordPress 主题提供API接口支持

使用[wp-api-menus](https://github.com/unfulvio/wp-api-menus)插件实现格式化导航栏信息获取

## 本插件提供了以下额外接口

所有接口路由前缀为 `hachimi/v1`。

---

### 1. 站点配置

获取站点基本配置信息。

```
GET /hachimi/v1/settings
```

**权限**: 公开

**响应示例**:

```json
{
  "site_name": "站点名称",
  "site_description": "站点描述",
  "posts_per_page": 10,
  "comments_per_page": 50,
  "sticky_post_ids": [1, 2, 3],
  "time_zone": "Asia/Shanghai",
  "global_style": "/* 全局样式表 */"
}
```

---

### 2. 导航栏

获取 hachimi 主题导航栏菜单。依赖 [wp-api-menus](https://github.com/unfulvio/wp-api-menus) 插件。

```
GET /hachimi/v1/navigation
```

**权限**: 公开

**说明**: 如尚未创建菜单，会自动新建一个包含首页链接的默认菜单。

---

### 3. 前端路由解析

根据 URL 路径解析 WordPress 路由，返回对应的页面/文章/分类等信息。用于 headless 前端路由匹配。

```
GET /hachimi/v1/route?path=<url路径>
```

**权限**: 公开

**参数**:

| 参数 | 类型   | 必填 | 说明                                   |
| ---- | ------ | ---- | -------------------------------------- |
| path | string | 是   | URL路径，如 `/about`、`/category/news` |

**响应类型**:

| type       | 说明                                                                                           |
| ---------- | ---------------------------------------------------------------------------------------------- |
| `home`     | 首页                                                                                           |
| `page`     | 页面，返回 `id` 和 `canonical`                                                                 |
| `single`   | 文章/自定义文章类型，返回 `id`、`post_type` 和 `canonical`                                     |
| `category` | 分类归档，返回 `id`、`title`、`description`、`canonical`                                       |
| `tag`      | 标签归档，返回 `id`、`title`、`description`、`canonical`                                       |
| `taxonomy` | 自定义分类法归档，返回 `taxonomy`、`slug`、`canonical`                                         |
| `author`   | 作者归档，返回 `author_id`、`name`、`description`、`post_count`、`slug`、`avatar`、`canonical` |
| `search`   | 搜索结果页，返回 `query`                                                                       |
| `static`   | 静态文件，返回 `file_path`                                                                     |
| `404`      | 未找到                                                                                         |

---

### 4. 友情链接

获取 WordPress 链接管理器中的所有链接及分类。

```
GET /hachimi/v1/links
```

**权限**: 公开

**响应示例**:

```json
{
  "categories": [
    {
      "id": 1,
      "name": "友情链接",
      "description": "友情链接描述"
    }
  ],
  "links": [
    {
      "id": 1,
      "name": "链接名称",
      "description": "链接描述",
      "url": "https://example.com",
      "image": "https://example.com/icon.png",
      "priority": 10,
      "category_ids": [1]
    }
  ]
}
```

---

### 5. 文章浏览量

获取或增加文章阅读量。

```
GET  /hachimi/v1/views?post_id=<文章ID>
POST /hachimi/v1/views
```

**权限**: 公开

**参数**:

| 参数    | 类型    | 必填 | 说明         |
| ------- | ------- | ---- | ------------ |
| post_id | integer | 是   | 文章/页面 ID |

**GET 请求** - 获取当前阅读量，返回纯数字。
**POST 请求** - 阅读量 +1，返回增加后的阅读量。

同时也向 WPGraphQL 注册了 `views` 字段，可在 GraphQL 查询中直接获取文章和页面的阅读量。

---

### 6. 缓存管理

基于 WordPress Transients API 的键值缓存管理。

```
GET    /hachimi/v1/cache?key=<缓存键>
PUT    /hachimi/v1/cache
DELETE /hachimi/v1/cache?key=<缓存键>
```

**权限**: 管理员 (`manage_options`)

**参数**:

| 参数   | 类型    | 必填       | 说明                               |
| ------ | ------- | ---------- | ---------------------------------- |
| key    | string  | 是         | 缓存键名                           |
| value  | mixed   | PUT 时必填 | 缓存值                             |
| expire | integer | 否         | 过期时间（秒），默认 3600（1小时） |

**PUT 请求体** (JSON):

```json
{
  "key": "my_cache_key",
  "value": { "any": "data" },
  "expire": 3600
}
```

---

### 7. 主题选项

管理 hachimi 主题的自定义配置选项。

```
GET /hachimi/v1/option?key=<选项键>
PUT /hachimi/v1/option
```

**权限**: 管理员 (`manage_options`)

**GET 参数**:

| 参数 | 类型   | 必填 | 说明                                          |
| ---- | ------ | ---- | --------------------------------------------- |
| key  | string | 是   | 选项键名（会自动添加 `hachimi_option_` 前缀） |

**PUT 请求体** (JSON):

```json
{
  "key": "theme_color",
  "value": "#ff6600"
}
```

---

### 8. Headless 模板渲染

通过添加 `?headless` 查询参数，使用专用模板渲染页面，输出经过 WordPress 完整处理（含古腾堡区块渲染、短代码等）的 HTML 内容。输出以 `<!-- hachimi-headless-start -->` 和 `<!-- hachimi-headless-end -->` 包裹，方便 headless 前端抓取解析。

```
任意WordPress页面 ?headless
```

例如:

```
https://example.com/about/?headless
```

---

### 9. 小工具渲染 (暂未启用)

通过添加 `?widget` 查询参数，渲染 hachimi 主题侧边栏小工具区域的 HTML 内容。输出以 `<!-- hachimi-widget-start -->` 和 `<!-- hachimi-widget-end -->` 包裹。

> **注意**: 由于古腾堡区块样式隔离尚未实现，此功能暂未启用。

---

### 10. 评论身份支持

为 WPGraphQL 的 `createComment` 变更提供用户身份验证支持。通过传递 `token` 参数，在创建评论时自动设置 WordPress 当前用户身份，使评论与用户关联。

**使用方式**: 在 WPGraphQL 请求中传入 `token` 字段。

---

### 11. 页面摘要支持（可用于简易的SEO人工摘要支持）

为 WordPress 页面（page）启用摘要（excerpt）功能，使其在 REST API 和 WPGraphQL 的响应中包含摘要字段。

## Headless WordPress 统一认证系统

本插件内置了一套基于 Token 的统一认证系统，实现 headless 前端与 WordPress 后端的用户身份互通。用户通过用户名密码获取 Token，在 REST API / WPGraphQL 请求中携带 Token 来标识身份。

### Token 生命周期

- **签发 (Issue)**: 登录成功后返回原始 Token 字符串及过期时间，服务端仅存储 Token 的 SHA-256 哈希值
- **验证 (Verify)**: 通过 Token 哈希值查询用户信息并检查是否过期
- **刷新 (Refresh)**: 吊销旧 Token，签发新 Token（延长有效期）
- **吊销 (Revoke)**: 支持吊销单个 Token、指定用户的所有 Token、或所有用户的全部 Token

### 认证 API

#### POST /hachimi/v1/auth/login

使用用户名密码登录，获取 Token 及用户信息。

**权限**: 站点管理员 (`manage_options`)

**请求体** (JSON):

```json
{
  "username": "admin",
  "password": "password"
}
```

**响应示例**:

```json
{
  "token": "a1b2c3d4e5...64位十六进制字符串",
  "user": {
    "id": 1,
    "name": "管理员",
    "email": "admin@example.com",
    "roles": ["administrator"],
    "description": "个人简介",
    "slug": "admin",
    "avatar": {
      "url_96": "https://...96.jpg",
      "url_150": "https://...150.jpg",
      "url_300": "https://...300.jpg"
    },
    "management": {
      "admin": "https://example.com/wp-admin/",
      "newpost": "https://example.com/wp-admin/post-new.php"
    },
    "expire": 1730000000
  }
}
```

> `management` 字段仅在用户拥有 `edit_posts` 权限时返回。

---

#### POST /hachimi/v1/auth/validate

验证 Token 是否有效，返回用户信息。

**权限**: 站点管理员 (`manage_options`)

**请求体** (JSON):

```json
{
  "token": "your-token-string"
}
```

**响应示例** (与 login 的 user 对象结构一致):

```json
{
  "id": 1,
  "name": "管理员",
  "email": "admin@example.com",
  "roles": ["administrator"],
  "description": "个人简介",
  "slug": "admin",
  "avatar": {
    "url_96": "https://...96.jpg",
    "url_150": "https://...150.jpg",
    "url_300": "https://...300.jpg"
  },
  "management": {
    "admin": "https://example.com/wp-admin/",
    "newpost": "https://example.com/wp-admin/post-new.php"
  },
  "expire": 1730000000
}
```

---

#### POST /hachimi/v1/auth/refresh

刷新 Token：吊销当前 Token，签发新 Token，延长有效期。

**权限**: 站点管理员 (`manage_options`)

**请求体** (JSON):

```json
{
  "token": "current-token-string"
}
```

**响应示例**:

```json
{
  "token": "new-token-string",
  "expire": 1730000000
}
```

---

#### POST /hachimi/v1/auth/sso/exchange

SSO 单点登录：使用一次性 code 兑换 Token。该 code 通过管理后台工具栏的 SSO 跳转生成，有效期 3 分钟。

**权限**: 站点管理员 (`manage_options`)

**请求体** (JSON):

```json
{
  "code": "sso-code-string"
}
```

**响应示例**:

```json
{
  "token": "exchanged-token-string",
  "expire": 1730000000
}
```

---

### 管理后台 SSO 单点登录

在 WordPress 管理后台中，顶部工具栏的"查看站点"链接会被替换为 SSO 跳转链接。点击后：

1. 生成一次性 code（有效期 3 分钟），存储于 Transients
2. 重定向至前端 SSO 页面：`https://<frontend_domain>/sso?code=<code>`
3. 前端通过 `POST /hachimi/v1/auth/sso/exchange` 兑换 Token

**前台域名配置**: 在 WordPress 后台 → Hachimi 设置 → 前台域名 中设置。

---

### Token 吊销管理

在 WordPress 后台 → Hachimi 设置 页面中，管理员可以：

- **吊销指定用户**：选择用户后吊销其所有 Token，该用户所有前台登录状态立即失效
- **吊销所有用户**：一键吊销所有 Token，强制所有用户重新登录

Token 数据库每天自动清理过期记录。

---

## 额外古腾堡块

本插件额外提供了一些古腾堡块支持。  
