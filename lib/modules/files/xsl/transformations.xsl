<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content[@name='list']" mode="content">
    <h2>Трансформация картинок</h2>
    <xsl:apply-templates select="items" />
  </xsl:template>

  <xsl:template match="items[not(item)]">
    <p>Трансформация картинок позволяет автоматически преобразовывать загружаемые файлы в несколько разных вариантов, например, автоматически создать маленькую квадратную копию картинки для использования на главной странице.</p>
    <p>На данный момент ни одной трансформации нет, но вы можете <a href="admin/system/settings/imgtransform/add">добавить новое правило</a>.</p>
  </xsl:template>

  <xsl:template match="items[item]">
    <form method="get" action="admin/system/settings/imgtransform/delete">
      <table class="classic">
        <thead>
          <tr>
            <th/>
            <th>Имя</th>
            <th>X</th>
            <th>Y</th>
            <th>Режим</th>
            <th>Формат</th>
            <th>Качество</th>
          </tr>
        </thead>
        <tbody>
          <xsl:for-each select="item">
            <xsl:sort select="@name" />
            <tr>
              <td>
                <input type="checkbox" name="name[]" value="{@name}" />
              </td>
              <td>
                <a href="admin/system/settings/imgtransform/edit?name={@name}">
                  <xsl:value-of select="@name" />
                </a>
              </td>
              <td>
                <xsl:value-of select="@width" />
              </td>
              <td>
                <xsl:value-of select="@height" />
              </td>
              <td>
                <xsl:choose>
                  <xsl:when test="@scalemode='crop'">с обрезанием</xsl:when>
                  <xsl:when test="@scalemode='scale'">простой</xsl:when>
                </xsl:choose>
              </td>
              <td>
                <xsl:value-of select="@format" />
              </td>
              <td>
                <xsl:value-of select="@quality" />
                <xsl:text>%</xsl:text>
              </td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
      <input type="hidden" name="destination" />
      <input type="submit" value="Удалить отмеченные" /> или <a href="admin/system/settings/imgtransform/add">добавить новое</a>
    </form>
  </xsl:template>
</xsl:stylesheet>
