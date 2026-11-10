# 维护公告功能实现方案

## Context

后台需要在"店铺管理"菜单下新增"维护公告"页面。管理员可以开启/关闭维护模式，并设定富文本公告内容。开启后通过 SSE 通知网站，网站隐藏所有内容，只显示维护公告。

## 实现方案

### 一、后端 (Laravel)

#### 1. 数据库迁移：新建 `maintenance_notice` 表
**文件**: `Backend/database/migrations/2026_10_12_000001_create_maintenance_notice_table.php`

```php
Schema::create('maintenance_notice', function (Blueprint $table) {
    $table->tinyIncrements('id');
    $table->tinyInteger('maintenance_enabled')->default(0);   // 0=关 1=开
    $table->longText('notice_content')->nullable();            // 繁体/中文公告
    $table->longText('en_notice_content')->nullable();         // 英文公告
    $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
});
// 插入默认行 id=1
```

#### 2. 菜单记录：在 `menu` 表中新增"维护公告"子菜单
在迁移中 `DB::table('menu')->insert` 一条记录：
- `parent` = 29（店铺管理父菜单 ID）
- `menu_name` = '维护公告', `en_menu_name` = 'Maintenance Notice'
- `path` = 'maintenance-notice'
- `status` = 1
- `icon` = 'el-icon-notice'

> 路由 `path` = `maintenance-notice`，前端 `map.js` 自动将其映射为 `MaintenanceNotice` → 加载 `@/views/shop/maintenance-notice/index.vue`

#### 3. 控制器：`Admin\MaintenanceNoticeController`
**文件**: `Backend/app/Http/Controllers/Admin/MaintenanceNoticeController.php`

两个方法，复用现有 Controller 基类的 `success()` / `error()`：
- `getMaintenanceNoticeWeb()` — 查询 `maintenance_notice` where id=1
- `editMaintenanceNoticeWeb(Request)` — 更新 `maintenance_enabled`、`notice_content`、`en_notice_content`

#### 4. 后台路由
**文件**: `Backend/routes/web.php`（在店铺设置块 L109 附近添加）

```php
Route::post("getMaintenanceNoticeWeb", "Admin\MaintenanceNoticeController@getMaintenanceNoticeWeb");
Route::post("editMaintenanceNoticeWeb", "Admin\MaintenanceNoticeController@editMaintenanceNoticeWeb");
```

#### 5. APP/网站端 SSE + API 端点

**API 路由**（`routes/api.php`）：
```php
Route::get("/maintenance/status", "Api\MaintenanceSseController@status");       // 获取当前维护状态（公开）
Route::get("/maintenance/stream", "Api\MaintenanceSseController@stream");        // SSE 维护状态推送流（公开）
```

**控制器**：`Api\MaintenanceSseController`（新建）
- `status()` — 返回 `{maintenance_enabled, notice_content, en_notice_content}`
- `stream()` — SSE 流，每 2 秒轮询 `maintenance_notice` 表，状态变化时推送 `maintenance.updated` 事件，25 秒后断开重连
- 两个端点均无需认证（公开访问）

SSE 事件格式：
```
event: maintenance.updated
data: {"maintenance_enabled":1,"notice_content":"..."}
```

### 二、前端 (Vue)

#### 1. 新建页面
**文件**: `Frontend/src/views/shop/maintenance-notice/index.vue`

参考 `agreement/user-agreement/index.vue` 的模式：
- `el-card` 容器 + 标题 + 保存按钮
- `el-switch` 开关（maintenance_enabled，active-value=1，inactive-value=0）
- `Tinymce` 富文本编辑器（notice_content）— 复用 `@/components/Tinymce`
- `Tinymce` 富文本编辑器（en_notice_content）— 英文公告
- `getMaintenanceNotice()` 加载数据，`editMaintenanceNotice(data)` 保存

#### 2. API 封装
**文件**: `Frontend/src/api/index.js`（在店铺设置相关块添加）

```js
export const getMaintenanceNotice = () => request({ url: '/getMaintenanceNoticeWeb', method: 'post' })
export const editMaintenanceNotice = data => request({ url: '/editMaintenanceNoticeWeb', method: 'post', data })
```

### 三、网站端对接（APP/前端页面）

网站在加载时先调 `GET /api/maintenance/status`：
- 若 `maintenance_enabled = 1`：隐藏所有内容，显示 `notice_content`（或按语言显示对应内容）
- 同时打开 `GET /api/maintenance/stream` 监听状态变化，收到 `maintenance.updated` 事件后切换 UI

## 文件清单

| 操作 | 文件 |
|------|------|
| 新建 | `Backend/database/migrations/2026_10_12_000001_create_maintenance_notice_table.php` |
| 新建 | `Backend/app/Http/Controllers/Admin/MaintenanceNoticeController.php` |
| 新建 | `Backend/app/Http/Controllers/Api/MaintenanceSseController.php` |
| 改 | `Backend/routes/web.php` — 添加 2 条后台路由 |
| 改 | `Backend/routes/api.php` — 添加 2 条公开 API 路由 |
| 新建 | `Frontend/src/views/shop/maintenance-notice/index.vue` |
| 改 | `Frontend/src/api/index.js` — 添加 2 个 API 函数 |

## 参考文件（复用模式）
- `Frontend/src/views/agreement/user-agreement/index.vue` — Tinymce 富文本页面模式
- `Frontend/src/components/Tinymce/index.vue` — 富文本编辑器组件
- `Frontend/src/views/shop/store-settings/index.vue` — el-switch 开关使用模式
- `Backend/app/Http/Controllers/Admin/ShopGeTuiController.php` — 简单 CRUD 控制器模式
- `Backend/app/Http/Controllers/Api/BalanceSseController.php` — SSE 控制器模式

## 验证
1. `php artisan migrate` 执行迁移
2. 后台侧边栏"店铺管理"下出现"维护公告"菜单
3. 页面加载/保存正常，开关和富文本可编辑
4. `GET /api/maintenance/status` 返回正确状态
5. 打开 `GET /api/maintenance/stream`，后台切换开关，SSE 推送 `maintenance.updated` 事件
