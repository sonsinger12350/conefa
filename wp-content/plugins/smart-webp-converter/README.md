# Smart WebP Converter

Plugin WordPress tự động chuyển đổi ảnh sang định dạng WebP và giảm dung lượng ảnh một cách hợp lý.

## Tính năng

- ✅ Tự động chuyển đổi ảnh sang WebP khi upload
- ✅ Batch processing để chuyển đổi ảnh cũ
- ✅ Tự động serve WebP cho trình duyệt hỗ trợ
- ✅ Tùy chỉnh chất lượng WebP (quality)
- ✅ Resize ảnh lớn tự động
- ✅ Hỗ trợ tất cả thumbnail sizes
- ✅ Tương thích với GD và ImageMagick

## Yêu cầu hệ thống

- WordPress 5.0 trở lên
- PHP 7.4 trở lên
- PHP GD extension với WebP support HOẶC ImageMagick với WebP support

## Cài đặt

1. Copy thư mục `smart-webp-converter` vào `wp-content/plugins/`
2. Kích hoạt plugin trong WordPress Admin > Plugins
3. Vào Settings > WebP Converter để cấu hình

## Cấu hình

### General Settings

- **Auto Convert on Upload**: Tự động chuyển đổi ảnh sang WebP khi upload
- **WebP Quality**: Chất lượng WebP (1-100), khuyến nghị 80-85
- **Maximum Dimensions**: Kích thước tối đa (width/height), ảnh lớn hơn sẽ được resize
- **Serve WebP to Browsers**: Tự động serve WebP cho trình duyệt hỗ trợ
- **Delete Original After Conversion**: Xóa ảnh gốc sau khi chuyển đổi (không khuyến nghị)

### Batch Processing

Sử dụng công cụ Batch Processing để chuyển đổi tất cả ảnh đã upload trước đó sang WebP.

## Cách hoạt động

1. Khi upload ảnh mới, plugin tự động:
   - Chuyển đổi ảnh gốc sang WebP
   - Chuyển đổi tất cả thumbnail sizes sang WebP
   - Lưu file WebP cùng thư mục với ảnh gốc

2. Trên frontend:
   - Plugin tự động detect trình duyệt hỗ trợ WebP
   - Serve WebP cho trình duyệt hỗ trợ
   - Fallback về ảnh gốc cho trình duyệt không hỗ trợ

## Hỗ trợ định dạng

- JPEG/JPG
- PNG
- GIF

## Lưu ý

- Plugin không xóa ảnh gốc mặc định (trừ khi bật tùy chọn)
- File WebP được lưu cùng thư mục với ảnh gốc với extension `.webp`
- Batch processing có thể mất thời gian với số lượng ảnh lớn

## Phát triển

### Cấu trúc thư mục

```
smart-webp-converter/
├── smart-webp-converter.php    # File chính
├── includes/
│   ├── class-webp-converter.php      # Class xử lý chuyển đổi
│   ├── class-admin-settings.php     # Admin settings page
│   ├── class-batch-processor.php    # Batch processing
│   └── class-frontend-delivery.php  # Frontend delivery
├── assets/
│   └── js/
│       └── admin.js            # JavaScript cho admin
└── README.md
```

### Hooks

- `wp_generate_attachment_metadata`: Hook vào quá trình tạo metadata ảnh
- `wp_get_attachment_image_src`: Thay thế image src với WebP
- `the_content`: Thay thế ảnh trong content với WebP
- `post_thumbnail_html`: Thay thế thumbnail với WebP

## License

GPL v2 or later

## Tác giả

Your Name

